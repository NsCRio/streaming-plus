<?php

namespace App\Services\StreamingPlus;

use App\Models\Items;
use App\Services\IMDB\IMDBApiManager;
use Carbon\Carbon;

class ItemsSearchManager
{
    protected $searchTerm, $itemType, $results = [];

    public function __construct(string $searchTerm, string $itemType = null){
        $this->searchTerm = $searchTerm;
        $this->itemType = $itemType;
    }

    /**
     * @throws \Exception
     */
    public function search($forceOnline = false): static
    {
        if(!empty($this->searchTerm)){
            if(!$forceOnline)
                $this->searchOnLocal();
            if((empty($this->results) || count($this->results) == 0))
                $this->searchOnImdb();
        }
        return $this;
    }

    public function getResults(): array{
        return $this->results;
    }

    /**
     * @throws \Exception
     */
    public function searchOnLocal(): ItemsSearchManager
    {
        $query = Items::query()->where(function($query){
            $query->where('item_imdb_id', 'like', "%{$this->searchTerm}%")
                ->orWhere('item_title', 'like', "%{$this->searchTerm}%")
                ->orWhere('item_original_title', 'like', "%{$this->searchTerm}%")
                ->orWhereRaw("REPLACE(item_title, '-', ' ') LIKE '%{$this->searchTerm}%'")
                ->orWhereRaw("REPLACE(item_original_title, '-', ' ') LIKE '%{$this->searchTerm}%'")
                ->orWhereRaw("REPLACE(item_title, '-', '') LIKE '%{$this->searchTerm}%'")
                ->orWhereRaw("REPLACE(item_original_title, '-', '') LIKE '%{$this->searchTerm}%'");
            })->where('updated_at', '>=', Carbon::now()->subDay());
        if(isset($this->itemType))
            $query->where('item_category', $this->itemType);

        $results = [];
        if($query->count() > 0){
            foreach ($query->get() as $item){
                ItemsManager::putImdbDataToLocalStorage($item->getImdbData());
                $results[] = $item;
            }
        }
        $this->results = array_merge($this->results, $results);
        return $this;
    }

    public function searchOnImdb(): ItemsSearchManager
    {
        $api = new IMDBApiManager();
        $response = $api->search($this->searchTerm, $this->itemType);
        $results = [];
        foreach($response as $result){
            $results[] = ItemsManager::imdbDataToDatabase($result);
        }
        $this->results = array_merge($this->results, $results);
        return $this;
    }
}
