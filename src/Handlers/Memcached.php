<?php

namespace Lester\Forwarding\Handlers;

use Illuminate\Support\Collection;
use Lester\Forwarding\CallManager;
use Lester\Forwarding\Contracts\CallForwardingDriver;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Support\Str;
use Memcached as MemcachedCore;

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
            $config['options'] + [
                MemcachedCore::OPT_COMPRESSION => false,
            ],
            $creds
        );
        
    }
    
    public function putItem($key, $data): void
    {
        if ($this->connection->append("$key", "$data\n") === false) {
            if ($this->connection->set("$key", "$data\n") === false) {
                $this->memcachedError();
            }
        };
    }
    
    public function getAllItems($key, $purge = false): Collection
    {    
        $members = $this->connection->get($key);
           
        if ($members === false) {
            $this->memcachedError();
        }    
        
        $data = collect(explode("\n",$members))->filter(function($line) {
            return $line !== "";
        })->map(function($data) {
            return json_decode($data, true);
        });
        
        return $data;
    }
    
    private function memcachedError()
    {
        throw new \Exception("Memcached failed with code: " . $this->connection->getResultCode());
    }
}
