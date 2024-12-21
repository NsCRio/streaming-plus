<?php


use Carbon\Carbon;

return [
    md5('cache:clear') => [
        'Name' => 'Clears the cache of the whole app (searches, streams and addons)',
        'Category' => config('app.name'),
        'Description' => '',
        'Id' => md5('cache:clear'),
        'IsHidden' => false,
        'Key' => 'cache:clear',
        'LastExecutionResult' => [
            'EndTimeUtc' => Carbon::today()->format('Y-m-d')."T09:07:11.9647278Z",
            'Id' => md5('cache:flush'),
            'Key' => 'cache:flush',
            'StartTimeUtc' => Carbon::today()->format('Y-m-d')."T09:07:11.9290271Z",
            'Status' => 'Completed',
        ],
        'State' => 'Idle',
        'Triggers' => [
            [
                'IntervalTicks' => 864000000000,
                'Type' => 'IntervalTrigger'
            ]
        ],
    ],
    md5('library:update') => [
        'Name' => 'Cleans library, deletes items that have never been opened, updates TV series episodes, deletes old streams',
        'Category' => config('app.name'),
        'Description' => '',
        'Id' => md5('library:update'),
        'IsHidden' => false,
        'Key' => 'library:update',
        'LastExecutionResult' => [
            'EndTimeUtc' => Carbon::today()->format('Y-m-d')."T09:07:11.9647278Z",
            'Id' => md5('library:update'),
            'Key' => 'library:update',
            'StartTimeUtc' => Carbon::today()->format('Y-m-d')."T09:07:11.9290271Z",
            'Status' => 'Completed',
        ],
        'State' => 'Idle',
        'Triggers' => [
            [
                'IntervalTicks' => 864000000000,
                'Type' => 'IntervalTrigger'
            ]
        ],
    ],
    md5('library:clear') => [
        'Name' => '⚠️ Completely deletes the library and restores the initial configuration ⚠️',
        'Category' => config('app.name'),
        'Description' => '',
        'Id' => md5('library:clear'),
        'IsHidden' => false,
        'Key' => 'library:clear',
        'LastExecutionResult' => [
            'EndTimeUtc' => Carbon::today()->format('Y-m-d')."T09:07:11.9647278Z",
            'Id' => md5('library:clear'),
            'Key' => 'library:update',
            'StartTimeUtc' => Carbon::today()->format('Y-m-d')."T09:07:11.9290271Z",
            'Status' => 'Completed',
        ],
        'State' => 'Idle',
        'Triggers' => [
            [
                'IntervalTicks' => 864000000000,
                'Type' => 'IntervalTrigger'
            ]
        ],
    ]
];
