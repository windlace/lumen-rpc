<?php

namespace Cast\LumenRpc;

use PhpAmqpLib\Message\AMQPMessage;
use Thumper\BaseConsumer;

class RpcServer extends BaseConsumer
{
    /**
     * Initialize Server.
     * @param RpcMethod $rpcMethod
     */
    public function initServer(RpcMethod $rpcMethod)
    {
        $this->setExchangeOptions($rpcMethod->getExchangeOptions());
        $this->setQueueOptions($rpcMethod->getQueueOptions());
        $this->setRoutingKey($rpcMethod->getRoutingKey());
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
            $result = call_user_func($this->callback, unserialize($message->body));
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        } catch (\Exception $exception) {
            $result = $exception;
        }
        $this->sendReply(serialize($result), $message->get('reply_to'), $message->get('correlation_id'));
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
