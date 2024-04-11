<?php

namespace Lester\CallForwarding\Contracts;

use Illuminate\Support\Collection;

interface CallForwardingDriver
{
	public function getAllItems($key): Collection;
	public function putItem($key, $data): void;
}