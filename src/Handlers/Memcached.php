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
        
        $creds = $config['sasl'];
        if ($creds[0] === null) $creds = [];
        
        $this->connection = (new MemcachedConnector())->connect(
            $config['servers'],
            1,
            $config['options'],
            $creds,
        );
    }
    
    public function putItem($key, $data): void
    {
        $subKey = md5($data);
        
        if ($this->connection->set("{$key}:{$subKey}", $data, 60*60) === false) {
            $this->memcachedError();
        };
    }
    
    public function getAllItems($key, $purge = false): Collection
    {    
        $members = $this->connection->fetchAll();
        
        if ($members === false) {
            $this->memcachedError();
        }    
        return collect($members)->filter(function($item) {
            return Str::of($item)->contains("$key:");
        })->map(function($item) {
            
        });
    }
    
    private function memcachedError()
    {
        throw new \Exception("Memcached failed with code: " . $this->connection->getResultCode());
    }
}
