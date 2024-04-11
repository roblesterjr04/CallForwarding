<?php

namespace Lester\CallForwarding\Handlers;

use Lester\CallForwarding\CallManager;
use Lester\CallForwarding\Contracts\CallForwardingDriver;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Collection;

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
	
	public function getAllItems($key): Collection
	{
		$members = Redis::connection($this->connection)->smembers($key);
		
		return collect(array_map(function ($member) use ($key) {
			Redis::connection($this->connection)->srem($key, $member);
		
			return json_decode($member, true);
		}, $members));
	}
}