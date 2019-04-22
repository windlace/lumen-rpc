<?php

namespace Cast\LumenRpc;

use PhpAmqpLib\Message\AMQPMessage;
use Thumper\BaseConsumer;

class RpcServer extends BaseConsumer
{
    /**
     * @var bool
     */
    protected $serialize;

    /**
     * Initialize Server.
     * @param RpcMethod $rpcMethod
     */
    public function initServer(RpcMethod $rpcMethod)
    {
        $this->setExchangeOptions($rpcMethod->getExchangeOptions());
        $this->setQueueOptions($rpcMethod->getQueueOptions());
        $this->setRoutingKey($rpcMethod->getRoutingKey());
        $this->serialize = $rpcMethod->getSerialize();
    }

    /**
     * Start server.
     */
    public function start()
    {
        $this->setUpConsumer();

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * Process message.
     *
     * @param AMQPMessage $message
     * @throws \OutOfBoundsException
     * @throws \PhpAmqpLib\Exception\AMQPInvalidArgumentException
     */
    public function processMessage(AMQPMessage $message)
    {
        try {
            $msg = $this->serialize ? unserialize($message->body) : $message->body;
            $result = call_user_func($this->callback, $msg);
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        } catch (\Exception $exception) {
            $result = $exception;
        }

        $result = $this->serialize ? serialize($result) : $result;

        $this->sendReply($result, $message->get('reply_to'), $message->get('correlation_id'));
    }

    /**
     * Send reply.
     *
     * @param string $result
     * @param string $client
     * @param string $correlationId
     * @throws \PhpAmqpLib\Exception\AMQPInvalidArgumentException
     */
    protected function sendReply($result, $client, $correlationId)
    {
        $this->setParameter('correlation_id', $correlationId);
        $reply = new AMQPMessage(
            $result,
            $this->getParameters()
        );
        $this->channel
            ->basic_publish($reply, '', $client);
    }
}
