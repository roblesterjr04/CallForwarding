<?php

namespace Lester\Forwarding\Handlers;

use Illuminate\Support\Collection;
use Lester\Forwarding\CallManager;
use Lester\Forwarding\Contracts\CallForwardingDriver;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Support\Str;

class Memcached extends CallManager implements CallForwardingDriver
{
    private $connection;
    
    public function __construct()
    {
        $config = config('cache.stores.memcached');
        $this->connection = (new MemcachedConnector())->connect(
            $config['servers'],
            $config['persistent_id'],
            $config['options'],
            $config['sasl'],
        );
    }
    
    public function putItem($key, $data): void
    {
        $subKey = md5($data);
        
        $this->connection->set("{$key}_{$subKey}", $data);
    }
    
    public function getAllItems($key, $purge = false): Collection
    {    
        $members = $this->connection->fetchAll();
        
        if ($members === false) {
            throw new \Exception("Memcached failed with code: " . $this->connection->getResultCode());
        }    
        return collect($members)->filter(function($item) {
            return Str::of($item)->contains("$key_");
        })->map(function($item) {
            
        });
    }
}
