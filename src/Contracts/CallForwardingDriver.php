<?php

namespace Lester\Forwarding\Contracts;

use Illuminate\Support\Collection;

interface CallForwardingDriver
{
    public function getAllItems($key, $purge = true): Collection;

    public function putItem($key, $data): void;
}
