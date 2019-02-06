<?php

namespace Cast\LumenRpc\Facades;

use Illuminate\Support\Facades\Facade;

class RpcFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'rpc';
    }
}
