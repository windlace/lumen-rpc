<?php

namespace Cast\LumenRpc;

use Symfony\Component\OptionsResolver\OptionsResolver;

class RpcMethod
{
    /**
     * @var string
     */
    public $methodName;

    /**
     * @var string
     */
    public $exchangeName;

    /**
     * @var string
     */
    public $exchangeType;

    /**
     * @var string
     */
    public $queueName;

    /**
     * @var string
     */
    public $routing_key;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var bool
     */
    public $serialize;

    /**
     * RpcMethod constructor.
     * @param string $methodName
     * @param array $options
     */
    public function __construct($methodName, array $options = [])
    {
        $options            = $this->resolveOptions($options);
        $this->exchangeName = $this->formatExchangeName($methodName, $options);
        $this->exchangeType = $options['exchangeType'];
        $this->queueName    = $this->formatQueueName($methodName, $options);
        $this->routing_key  = $this->formatRoutingKey($methodName, $options);
        $this->serialize    = $options['serialize'];
        $this->methodName   = $methodName;
        $this->options      = $options;
    }

    /**
     * @return array
     */
    public function getExchangeOptions()
    {
        return [
            'name' => $this->exchangeName,
            'type' => $this->exchangeType,
        ];
    }

    /**
     * @return array
     */
    public function getQueueOptions()
    {
        return [
            'name' => $this->queueName,
        ];
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routing_key;
    }

    /**
     * @return string
     */
    public function getSerialize()
    {
        return $this->serialize;
    }

    /**
     * @param $methodName
     * @param array $options
     * @return string
     */
    public static function formatExchangeName($methodName, array $options) : string
    {
        return sprintf($options['exchangeNameFormat'], $methodName);
    }

    /**
     * @param $methodName
     * @param array $options
     * @return string
     */
    public static function formatQueueName($methodName, array $options) : string
    {
        return sprintf($options['queueNameFormat'], $methodName);
    }

    /**
     * @param $methodName
     * @param array $options
     * @return string
     */
    public static function formatRoutingKey($methodName, array $options) : string
    {
        return sprintf($options['routingKeyFormat'], $methodName);
    }

    /**
     * @param array $options
     * @return array
     */
    public function resolveOptions(array $options = []) : array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setDefined([
                'exchangeNameFormat',
                'exchangeType',
                'queueNameFormat',
                'routingKey',
                'serialize',
            ])
            ->setDefaults([
                'exchangeNameFormat' => config('rpc.exchange-name-format', 'rpc.%s-exchange'),
                'exchangeType'       => 'direct',
                'queueNameFormat'    => config('rpc.queue-name-format', 'rpc.%s-queue'),
                'routingKeyFormat'   => config('rpc.routing-key-format', 'rpc.%s'),
                'serialize'          => true,
            ])
            ->setAllowedTypes('exchangeNameFormat', 'string')
            ->setAllowedTypes('exchangeType', 'string')
            ->setAllowedTypes('queueNameFormat', 'string')
            ->setAllowedTypes('routingKeyFormat', 'string')
            ->setAllowedTypes('serialize', 'bool');

        return $resolver->resolve($options);
    }
}
