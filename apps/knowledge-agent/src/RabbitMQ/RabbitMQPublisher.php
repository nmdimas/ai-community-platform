<?php

declare(strict_types=1);

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQPublisher
{
    private const EXCHANGE = 'knowledge.direct';
    private const QUEUE = 'knowledge.chunks';
    private const DLQ = 'knowledge.dlq';
    private const DLX = 'knowledge.dlx';

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $rabbitmqUrl,
    ) {
    }

    /**
     * @param array<string, mixed> $chunk
     */
    public function publishChunk(array $chunk): void
    {
        $channel = $this->getChannel();

        $body = json_encode($chunk, \JSON_THROW_ON_ERROR);
        $message = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $channel->basic_publish($message, self::EXCHANGE, self::QUEUE);
    }

    private function getChannel(): AMQPChannel
    {
        if (null !== $this->channel && $this->channel->is_open()) {
            return $this->channel;
        }

        $this->connection = $this->createConnection();
        $channel = $this->connection->channel();
        \assert(null !== $channel);
        $this->channel = $channel;

        $this->declareTopology($channel);

        return $channel;
    }

    private function createConnection(): AMQPStreamConnection
    {
        $parsed = parse_url($this->rabbitmqUrl);
        \assert(false !== $parsed);

        return new AMQPStreamConnection(
            host: $parsed['host'] ?? 'rabbitmq',
            port: (int) ($parsed['port'] ?? 5672),
            user: urldecode($parsed['user'] ?? 'guest'),
            password: urldecode($parsed['pass'] ?? 'guest'),
            vhost: urldecode(ltrim($parsed['path'] ?? '/', '/')) ?: '/',
        );
    }

    private function declareTopology(AMQPChannel $channel): void
    {
        // Dead-letter exchange and queue
        $channel->exchange_declare(self::DLX, 'direct', false, true, false);
        $channel->queue_declare(self::DLQ, false, true, false, false);
        $channel->queue_bind(self::DLQ, self::DLX, self::DLQ);

        // Main exchange and queue with DLX configured
        $channel->exchange_declare(self::EXCHANGE, 'direct', false, true, false);
        $channel->queue_declare(self::QUEUE, false, true, false, false, false, [
            'x-dead-letter-exchange' => ['S', self::DLX],
            'x-dead-letter-routing-key' => ['S', self::DLQ],
        ]);
        $channel->queue_bind(self::QUEUE, self::EXCHANGE, self::QUEUE);
    }

    public function getDlqCount(): int
    {
        $channel = $this->getChannel();

        /** @var array{1: int} $result */
        $result = $channel->queue_declare(self::DLQ, true);

        return $result[1];
    }

    public function requeueDlq(int $limit = 100): int
    {
        $channel = $this->getChannel();
        $requeued = 0;

        for ($i = 0; $i < $limit; ++$i) {
            $msg = $channel->basic_get(self::DLQ);

            if (null === $msg) {
                break;
            }

            $republish = new AMQPMessage($msg->getBody(), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

            $channel->basic_publish($republish, self::EXCHANGE, self::QUEUE);
            $channel->basic_ack($msg->getDeliveryTag());
            ++$requeued;
        }

        return $requeued;
    }

    public function __destruct()
    {
        $this->channel?->close();
        $this->connection?->close();
    }
}
