<?php

namespace Lester\Forwarding\Handlers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Lester\Forwarding\CallManager;
use Lester\Forwarding\Contracts\CallForwardingDriver;

class RedisHandler extends CallManager implements CallForwardingDriver
{
    private $connection;

    public function __construct()
    {
        $this->connection = config('call-forwarding.redis_connection');
        Redis::connection($this->connection)->ping();
    }

    public function putItem($key, $data): void
    {
        Redis::connection($this->connection)->sadd($key, $data);
    }

    public function getAllItems($key, $purge = false): Collection
    {
        $members = Redis::connection($this->connection)->smembers($key);

        return collect(array_map(function ($member) use ($key, $purge) {
            if ($purge) Redis::connection($this->connection)->srem($key, $member);

            return json_decode($member, true);
        }, $members));
    }
}
