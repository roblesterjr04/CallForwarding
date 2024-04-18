<?php

namespace Lester\Forwarding;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Lester\Forwarding\Facades\Forward;
use Opis\Closure\SerializableClosure;

trait ReceivesCalls
{
    public $enableCallForwarding = false;

    public $afterCallForwarding = null;

    public static function bootReceivesCalls()
    {
        static::saving(function ($model) {
            if ($model->enableCallForwarding) {
                if ($model->exists) {
                    return $model->performUpdateTriage($model->getDirty());
                } else {
                    return $model->performCreateTriage($model->getDirty());
                }
            }
        });

        static::retrieved(function ($model) {
            $forwarded = $model->callForwardingGetQueue('update', $model->id, false);
            $model->forceFill($forwarded ?? []);
            if (count($forwarded ?? []) > 0) {
                $model->saveQuietly();
            }
        });
    }

    public function setAfterCallForwardingAttribute(Closure|string $callback)
    {
        if (is_string($callback)) {
            unset($this->attributes['afterCallForwarding']);
        }
    }

    public static function createTriage(array $attributes)
    {
        $attributes['updated_at'] = now();
        $attributes['created_at'] = now();
        $instance = new static($attributes);
        Forward::putItem($instance->callForwardingCacheSetsKey('insert'), json_encode($attributes));

        return new static($attributes);
    }

    public function performUpdateTriage(array $attributes)
    {
        $attributes['id'] = $this->id;
        $attributes['updated_at'] = now();
        if ($this->afterCallForwarding) {
            $attributes['afterCallForwarding'] = $this->serializeForwardCallback();
        }
        Forward::putItem($this->callForwardingCacheSetsKey('update'), json_encode($attributes));

        return false;
    }

    public function performCreateTriage(array $attributes)
    {
        $attributes['updated_at'] = now();
        $attributes['created_at'] = now();
        if ($this->afterCallForwarding) {
            $attributes['afterCallForwarding'] = $this->serializeForwardCallback();
        }
        Forward::putItem($this->callForwardingCacheSetsKey('insert'), json_encode($attributes));

        return false;
    }

    public function callForwardingGetQueue($prefix, $id = null, $purge = true)
    {
        $members = Forward::getAllItems($this->callForwardingCacheSetsKey($prefix), $purge);

        if ($id !== null) {
            return $members->where('id', $id)->first();
        }

        return $members;
    }

    public function callForwardingDataRegroup($data)
    {
        $groups = [];

        foreach ($data as $item) {
            foreach ($item as $key => $value) {
                if ($key !== 'id') {
                    // Initialize the group if it doesn't exist
                    if (! isset($groups[$key])) {
                        $groups[$key] = [];
                    }
                    // Assign value to group based on 'id'
                    $groups[$key][$item['id']] = $value;
                }
            }
        }

        // Convert array to collection if needed
        return collect($groups);
    }

    public function callForwardingTransitionInserts(): bool
    {
        $members = $this->callForwardingGetQueue('insert');

        if ($members->count() == 0) {
            return false;
        }

        $objects = $members->map(function ($item) {
            $instance = new static($item);
            $instance->afterForward(unserialize($item['afterCallForwarding'] ?? '')->getClosure());

            return $instance;
        });

        $keys = [];
        $params = [];

        foreach ($objects->toArray() as $subarray) {
            $keys = array_merge($keys, array_keys($subarray));
        }

        $keys = array_unique($keys);

        $values = implode(',', $objects->map(function ($model) use ($keys, &$params) {
            foreach ($keys as $key) {
                $row[$key] = '?';
                $params[] = $model->$key;
            }
            $closure = $model->afterCallForwarding;
            $closure($model->toArray());

            return '('.implode(',', $row).')';
        })->toArray());

        $keys = implode(',', $keys);
        $query = "INSERT INTO {$this->getTable()} ($keys) values $values";

        return \DB::insert($query, $params);
    }

    public function callForwardingTransitionUpdates(): int
    {
        $members = $this->callForwardingGetQueue('update');

        if ($members->count() == 0) {
            return 0;
        }

        $data = $this->callForwardingDataRegroup($members);
        $ids = $members->pluck('id')->toArray();
        $callbacks = $members->map(function ($member) {
            return ['id' => $member['id'], 'callback' => [
                unserialize($member['afterCallForwarding'] ?? ''),
                Arr::except($member, 'afterCallForwarding'),
            ]];
        })->pluck('callback', 'id');
        unset($data['afterCallForwarding']);

        $sets = [];
        $params = [];

        foreach ($data as $field => $row) {
            $cases = [];
            foreach ($row as $index => $value) {
                $cases[] = "WHEN {$index} then ?";
                $params[] = $value;
            }
            $cases = implode(' ', $cases);
            $sets[] = "`{$field}` = CASE `id` {$cases} END";
        }
        $sets = implode(', ', $sets);
        $ids = implode(',', $ids);

        $query = "UPDATE {$this->getTable()} SET $sets WHERE `id` in ({$ids})";
        $dbResult = \DB::update($query, $params);

        if ($dbResult > 0) {
            $this->afterForwardExecute($callbacks);
        }

        return $dbResult;
    }

    public function callForwardingCachePrefix()
    {
        return Str::snake(class_basename(static::class)).'_';
    }

    public function callForwardingCacheSetsKey($prefix)
    {
        return $this->callForwardingCachePrefix()."_set_$prefix";
    }

    public function forwarded(): self
    {
        try {
            // Attempt to get a value from Redis
            Forward::handler();

            $this->enableCallForwarding = true;
        } catch (\Exception $e) {

        }

        return $this;
    }

    public function afterForward(Closure $callback): self
    {
        $this->afterCallForwarding = $callback;

        return $this;
    }

    public function serializeForwardCallback()
    {
        if ($this->afterCallForwarding === null) {
            return null;
        }

        return serialize(new SerializableClosure($this->afterCallForwarding));
    }

    public function afterForwardExecute(Collection $callbacks)
    {
        foreach ($callbacks as $index => $callback) {
            if ($callback[0] !== false) {
                $callback[0]($callback[1]);
            }
        }
    }
}
