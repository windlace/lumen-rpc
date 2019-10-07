<?php

namespace Cast\LumenRpc;

use PhpAmqpLib\Connection\AbstractConnection;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
     * An array of options passed to the constructor.
     *
     * @var array Options.
     */
    protected $options;

    /**
     * Rpc constructor.
     * @param array $options
     * @throws \Exception
     */
    public function __construct(array $options = [])
    {
        $options = self::resolveOptions($options);

        $this->host               = $options['host'];
        $this->port               = $options['port'];
        $this->user               = $options['user'];
        $this->password           = $options['password'];
        $this->vhost              = $options['vhost'];
        $this->insist             = $options['insist'];
        $this->login_method       = $options['login_method'];
        $this->login_response     = $options['login_response'];
        $this->locale             = $options['locale'];
        $this->connection_timeout = $options['connection_timeout'];
        $this->read_write_timeout = $options['read_write_timeout'];
        $this->context            = $options['context'];
        $this->keepalive          = $options['keepalive'];
        $this->heartbeat          = $options['heartbeat'];
        $this->options            = $options;

        if (!class_exists($this->amqp_connection_class))
        {
            throw new \Exception("connection class does not exist");
        }
    }

    /**
     * @param array $options
     * @return array
     */
    public function resolveOptions(array $options = [])
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setRequired([
                'host',
                'port',
                'user',
            ])
            ->setDefined([
                'host',
                'port',
                'user',
                'password',
                'vhost',
                'insist',
                'login_method',
                'login_response',
                'locale',
                'connection_timeout',
                'read_write_timeout',
                'context',
                'keepalive',
                'heartbeat',
            ])
            ->setDefaults([
                'host'     => config('queue.connections.rabbitmq.host'),
                'port'     => config('queue.connections.rabbitmq.port'),
                'user'     => config('queue.connections.rabbitmq.login'),
                'password' => config('queue.connections.rabbitmq.password'),
                'vhost'    => config('queue.connections.rabbitmq.vhost'),
                'insist'   => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'connection_timeout' => 60,
                'read_write_timeout' => 60,
                'context' => null,
                'keepalive' => true,
                'heartbeat' => 30,
            ])
            ->setAllowedTypes('host', 'string')
            ->setAllowedTypes('port', 'integer')
            ->setAllowedTypes('user', 'string')
            ->setAllowedTypes('password', 'string')
            ->setAllowedTypes('vhost', 'string')
            ->setAllowedTypes('insist', 'bool')
            ->setAllowedTypes('login_method', 'string')
            ->setAllowedTypes('login_response', [\PhpAmqpLib\Wire\AMQPWriter::class, 'null'])
            ->setAllowedTypes('locale', 'string')
            ->setAllowedTypes('connection_timeout', 'integer')
            ->setAllowedTypes('read_write_timeout', 'integer')
            ->setAllowedTypes('context', ['resource', 'null'])
            ->setAllowedTypes('keepalive', 'bool')
            ->setAllowedTypes('heartbeat', 'integer');

        return $resolver->resolve($options);
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
     * @param RpcMethod $rpcMethod
     * @return RpcClient
     */
    public function initClient(RpcMethod $rpcMethod)
    {
        $connection = $this->getConnection();
        $client = new RpcClient($connection);
        $client->initClient($rpcMethod);
        return $client;
    }

    /**
     * init a rpc server
     * @param RpcMethod $rpcMethod
     * @return RpcServer
     */
    public function initServer(RpcMethod $rpcMethod)
    {
        $connection = $this->getConnection();
        $server = new RpcServer($connection);
        $server->initServer($rpcMethod);
        return $server;
    }

    /**
     * @param string $methodName
     * @param array $methodOptions
     * @return RpcMethod
     * @throws \Exception
     */
    public function initMethod($methodName, $methodOptions = [])
    {
        $availableMethods = $this->availableMethods();

        if (!array_key_exists($methodName, $availableMethods) && !in_array($methodName, $availableMethods)) {
            throw new \Exception("unknown RPC-method `{$methodName}`");
        }

        $methodOptions = $availableMethods[$methodName] ?? $methodOptions;

        return new RpcMethod($methodName, $methodOptions);
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
     * @param   string $methodName
     * @param   string|object|mixed $messageBody Any message object will be serialized
     *
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public static function get($methodName, $messageBody = null, array $options = [])
    {
        $rpc = new self($options);

        $rpcMethod = $rpc->initMethod($methodName);
        $rpcClient = $rpc->initClient($rpcMethod);
        $rpcClient->addRequest($messageBody, $rpcMethod->getRoutingKey());

        $replies = $rpcClient->getReplies();

        return array_pop($replies);
    }

    /**
     * @param $methodName
     *
     * Callable or array
     * @param $callback
     *
     * @param array $options
     * @throws \Exception
     * @link http://php.net/manual/en/function.is-callable.php
     */
    public static function listen($methodName, $callback, array $options = [])
    {
        $rpc = new self($options);

        $rpcMethod = $rpc->initMethod($methodName);
        $rpcServer = $rpc->initServer($rpcMethod);
        $rpcServer->setCallback($callback);

        $rpcServer->start();
    }
}
