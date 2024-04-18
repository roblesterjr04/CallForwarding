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
            $config['persistent_id'] ?? 'cfmc',
            $config['options'],
            $creds
        );
        
        dd($this->connection->isPersistent());
    }
    
    public function putItem($key, $data): void
    {
        $subKey = md5($data);
        
        if ($this->connection->set("$key:$subKey", $data) === false) {
            $this->memcachedError();
        };
    }
    
    public function getAllItems($key, $purge = false): Collection
    {    
        $members = $this->connection->getAllKeys();
           
        if ($members === false) {
            $this->memcachedError();
        }    
        
        $data = collect($members)->filter(function($item) use ($key) {
            return Str::of($item)->contains("$key:");
        })->map(function($key) {
            $data = $this->connection->get($key);
            if ($purge) $this->connection->delete($key);
            return $data;
        });
        
        return $data;
    }
    
    private function memcachedError()
    {
        throw new \Exception("Memcached failed with code: " . $this->connection->getResultCode());
    }
}
