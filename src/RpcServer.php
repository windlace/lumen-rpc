<?php

namespace Cast\LumenRpc;

use PhpAmqpLib\Message\AMQPMessage;

class RpcServer extends \Thumper\RpcServer {
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
}
