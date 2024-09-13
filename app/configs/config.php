<?php

return [
    'settings' => [
        'displayErrorDetails' => true,

        'logger' => [
            'name' => 'vixedin',
            'level' => \Monolog\Logger::DEBUG,
            'path' => LOG_PATH,
        ],
        'email' => [],
    ],
    'url' => [
        'baseUrl' => BASE_URL,
    ],
];
