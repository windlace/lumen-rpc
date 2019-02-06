<?php

namespace Cast\LumenRpc;

use Illuminate\Console\Command;

abstract class RpcLumenCommand extends Command
{
    // "cmd://commandName@appName/rpcMethodName"
    protected static $dsn;

    public function fail($trace)
    {
        return ['ok'=>0,'dsn'=>static::$dsn,'trace'=>$trace];
    }

    public function done($data)
    {
        return ['ok'=>1,'dsn'=>static::$dsn,'data'=>$data];
    }

    public function callback($msg)
    {
        try {
            return $this->done($this->processMessage($msg));
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}