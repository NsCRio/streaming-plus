<?php

namespace App\Services\Tasks;

use Carbon\Carbon;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TaskManager
{
    protected $task;

    public function __construct(string $taskId){
        $this->task = @self::getTaskList()[$taskId];
    }

    public static function getTaskList(){
        return config('tasks');
    }

    public function getTask(){
        return $this->task;
    }

    public function exists(): bool {
        return isset($this->task);
    }

    public function executeTask(): bool {
        Log::info("Executing Task: ".$this->task['Key']);
        Artisan::call($this->task['Key']);
        return true;
    }

}
