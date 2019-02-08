<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | %s means config key
    |
    */

    'exchange-name-format' => 'rpc.%s-exchange',
    'queue-name-format'    => 'rpc.%s-queue',
    'routing-key-format'   => '%s',

    /*
    |--------------------------------------------------------------------------
    | RPC-methods list
    |--------------------------------------------------------------------------
    |
    */

    'config' =>  [

        // auto-declaration example:
        'api-v1-add-user',

        // overwriting default params example:
        'user-v1-get-paid-platforms' => [
            'queueNameFormat' => 'rpc.frontend.get-paid-platforms',
            'routingKeyFormat' => 'frontend.get-paid-platforms',
            'exchangeNameFormat' => 'exchange.rpc',
            'exchangeType' => 'direct',
        ],

    ],

];
