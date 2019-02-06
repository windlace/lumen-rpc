<?php

namespace Cast\LumenRpc;

use PhpAmqpLib\Connection\AbstractConnection;

class Rpc
{
    /**
     * @var string
     */
    public $host;
    /**
     * @var int
     */
    public $port;
    /**
     * @var string
     */
    public $user;
    /**
     * @var string
     */
    public $password;
    /**
     * @var string connection class
     */
    public $amqp_connection_class = \PhpAmqpLib\Connection\AMQPLazyConnection::class;
    /**
     * @var string
     */
    public $vhost = '/';
    /**
     * @var bool
     */
    public $insist = false;
    /**
     * @var string
     */
    public $login_method = 'AMQPLAIN';
    /**
     * @var mixed
     */
    public $login_response = null;
    /**
     * @var string
     */
    public $locale = 'en_US';
    /**
     * @var float
     */
    public $connection_timeout = 3.0;
    /**
     * @var float
     */
    public $read_write_timeout = 3.0;
    /**
     * @var null|mixed
     */
    public $context = null;
    /**
     * @var bool
     */
    public $keepalive = false;
    /**
     * @var float
     */
    public $heartbeat = 0;
    /**
     * @var null|AbstractConnection
     */
    protected $amqp_connection = null;

    /**
     * Rpc constructor.
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $vhost
     * @param bool $insist
     * @param string $login_method
     * @param null $login_response
     * @param string $locale
     * @param int $connection_timeout
     * @param int $read_write_timeout
     * @param null $context
     * @param bool $keepalive
     * @param int $heartbeat
     * @throws \Exception
     */
    public function __construct(
        $host     = null,
        $port     = null,
        $user     = null,
        $password = null,
        $vhost    = null,
        $insist   = false,
        $login_method = 'AMQPLAIN',
        $login_response = null,
        $locale = 'en_US',
        $connection_timeout = 60,
        $read_write_timeout = 60,
        $context = null,
        $keepalive = true,
        $heartbeat = 30
    )
    {

        $this->host               = config('queue.connections.rabbitmq.host',     $host);
        $this->port               = config('queue.connections.rabbitmq.port',     $port);
        $this->user               = config('queue.connections.rabbitmq.login',    $user);
        $this->password           = config('queue.connections.rabbitmq.password', $password);
        $this->vhost              = config('queue.connections.rabbitmq.vhost',    $vhost);
        $this->insist             = $insist;
        $this->login_method       = $login_method;
        $this->login_response     = $login_response;
        $this->locale             = $locale;
        $this->connection_timeout = $connection_timeout;
        $this->read_write_timeout = $read_write_timeout;
        $this->context            = $context;
        $this->keepalive          = $keepalive;
        $this->heartbeat          = $heartbeat;


        foreach (['host', 'user', 'port'] as $configParam)
        {
            if (empty($this->{$configParam})) {
                throw new \Exception("{$configParam} cannot be empty");
            }
        }
        if (!class_exists($this->amqp_connection_class))
        {
            throw new \Exception("connection class does not exist");
        }
    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        foreach (['host', 'user', 'port'] as $configParam)
        {
            if (empty($this->{$configParam})) {
                throw new \Exception("{$configParam} cannot be empty");
            }
        }
        if (!class_exists($this->amqp_connection_class))
        {
            throw new \Exception("connection class does not exist");
        }
    }
    /**
     * init a rpc client
     * @param $exchangeName
     * @return RpcClient
     */
    public function initClient($exchangeName)
    {
        $connection = $this->getConnection();
        $client = new RpcClient($connection);
        $client->initClient($exchangeName);
        return $client;
    }
    /**
     * init a rpc server
     * @param $exchangeName
     * @return RpcServer
     */
    public function initServer($exchangeName)
    {
        $connection = $this->getConnection();
        $server = new RpcServer($connection);
        $server->initServer($exchangeName);
        return $server;
    }
    /**
     * @return null|AbstractConnection
     */
    protected function getConnection()
    {
        if ($this->amqp_connection == null) {
            $this->amqp_connection = new $this->amqp_connection_class(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost,
                $this->insist,
                $this->login_method,
                $this->login_response,
                $this->locale,
                $this->connection_timeout,
                $this->read_write_timeout,
                $this->context,
                $this->keepalive,
                $this->heartbeat
            );
        }
        return $this->amqp_connection;
    }

    public function availableMethods()
    {
        return config('rpc.config');
    }

    /**
     * @param   string              $methodName
     * @param   string|object|mixed $messageBody    Any message object will be serialized
     *
     * @throws \Exception
     * @return array
     */
    public static function get($methodName, $messageBody = null)
    {
        $rpc = new self();

        $availableMethods = $rpc->availableMethods();

        if (!array_key_exists($methodName, $availableMethods)) {
            throw new \Exception("unknown RPC-method `{$methodName}`");
        }

        $rpcClient = $rpc->initClient($availableMethods[$methodName]['exchangeName']);
        $rpcClient->addRequest($messageBody);

        $replies = $rpcClient->getReplies();

        return array_pop($replies);
    }

    /**
     * @param $methodName
     *
     * Callable or array
     * @link http://php.net/manual/en/function.is-callable.php
     * @param $callback
     *
     * @throws \Exception
     */
    public static function listen($methodName, $callback)
    {
        $rpc = new self();

        $availableMethods = $rpc->availableMethods();

        if (!array_key_exists($methodName, $availableMethods)) {
            throw new \Exception("unknown RPC-method `{$methodName}`");
        }

        $rpcServer = $rpc->initServer($availableMethods[$methodName]['exchangeName']);
        $rpcServer->setCallback($callback);

        $rpcServer->start();
    }
}