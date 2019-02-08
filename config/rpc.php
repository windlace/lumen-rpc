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
        'my-method',

        // overwriting default params example:
        'another-method' => [
            'queueNameFormat' => 'queue.rpc.%s',
            'routingKeyFormat' => '%s-my-suffix',
            'exchangeNameFormat' => 'my-custom-rpc-exchange-%s',
            'exchangeType' => 'direct',
        ],

    ],

];
