<?php

namespace Services;

use Psr\Log\LoggerInterface;
use Swoole\Coroutine\Redis;
use Swoole\Coroutine\Channel;

class RedisPool
{
    /** @var LoggerInterface */
    private $logger;

    /** @var Channel */
    private $pool;

    private $host;
    private $port;
    private $maxPoolSize;
    private $poolSize = 0;
    private $db;

    private $connected = false;

    public function __construct(
        int $poolSize = 5,
        int $db,
        LoggerInterface $logger
    ) {
        $this->pool = new Channel(
            $poolSize
        );

        $this->maxPoolSize = $poolSize;
        $this->db = $db;
        $this->logger = $logger;
    }

    public function connect(
        string $host = '127.0.0.1',
        int $port = 6379
    ) {
        if ($this->isConnected()) {
            throw new \RuntimeException(
                'RedisPool is initialized already'
            );
        }

        $this->host = $host;
        $this->port = $port;

        while ($this->canIncreasePool()) {
            $this->appendClient();
        }

        $this->connected = true;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function get(): Redis
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException(
                'RedisPool is closed'
            );
        }

        if (!$this->pool->length()) {
            $this->logger->info(
                "Waiting for available redis client"
            );
        }

        return $this
            ->pool
            ->pop();
    }

    public function close()
    {
        $this
            ->pool
            ->close();

        $this->logger->info(
            "Redis pool closed"
        );
        $this->connected = false;
    }

    public function push(Redis $redis)
    {
        $redis->close();
        unset($redis);
        $this->poolSize--;

        $this
            ->appendClient();
    }

    private function canIncreasePool()
    {
        return $this->maxPoolSize > $this->poolSize;
    }

    private function appendClient(): bool
    {
        if (!$this->canIncreasePool()) {
            $this->logger->debug(
                "Current redis pool size has reached it's limit"
            );
            return false;
        }

        $redisClient = RedisClientFactory::create(
            $this->host,
            $this->port
        );

        $redisClient->select($this->db);

        $this->poolSize++;

        $this->logger->debug(
            "Current Redis pool size is " . $this->poolSize
        );

        $this
            ->pool
            ->push(
                $redisClient
            );

        return true;
    }
}
