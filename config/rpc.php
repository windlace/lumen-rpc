<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | %s means config key
    |
    | All defaults are optional.
    | @see Cast\LumenRpc\RpcMethod\resolveOptions
    |
    */

    'exchange-name-format'    => 'rpc.%s-exchange',
    'queue-name-format'       => 'rpc.%s-queue',
    'routing-key-format'      => '%s',
    'default-request-timeout' => 0, // in seconds (0 - infinity)
    'consumer-tag-auto'       => false,

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
