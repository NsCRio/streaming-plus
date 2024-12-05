<?php

namespace App\Services\StreamingPlus;

use App\Models\Items;
use App\Services\IMDB\IMDBApiManager;
use Carbon\Carbon;

class ItemsSearchManager
{
    protected $searchTerm, $results = [];

    public function __construct($searchTerm){
        $this->searchTerm = $searchTerm;
    }

    /**
     * @throws \Exception
     */
    public function search($forceOnline = false): static
    {
        if(!empty($this->searchTerm)){
            $results = [];
            if(!$forceOnline)
                $results = $this->searchOnLocal();
            if((empty($results) || count($results) == 0))
                $results = $this->searchOnImdb();
            $this->results = $results;
        }
        return $this;
    }

    public function getResults(): array{
        return $this->results;
    }

    /**
     * @throws \Exception
     */
    protected function searchOnLocal(): array
    {
        $query = Items::query()
            ->whereField('imdb_id', 'like', "%{$this->searchTerm}%")
            ->orWhereField('title', 'like', "%{$this->searchTerm}%")
            ->orWhereField('original_title', 'like', "%{$this->searchTerm}%")
            ->where('updated_at', '>=', Carbon::now()->subDays(2));
        $results = [];
        if($query->count() > 0){
            foreach ($query->get() as $item){
                ItemsManager::putImdbDataToLocalStorage($item->getImdbData());
                $results[] = $item;
            }
        }
        return $results;
    }

    protected function searchOnImdb(): array
    {
        $api = new IMDBApiManager();
        $response = $api->search($this->searchTerm, null, 1);
        $results = [];
        foreach($response as $result){
            $results[] = ItemsManager::imdbDataToDatabase($result);
        }
        return $results;
    }
}
