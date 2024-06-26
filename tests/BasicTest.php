<?php

namespace Tests;

use Lester\Forwarding\Facades\Forward;
use Tests\Fixtures\TestModel;

class BasicTest extends TestCase
{
    public function testPackageInitializes(): void
    {
        $this->assertTrue(true);
    }

    public function testModelInstantiates(): void
    {
        $model = new TestModel;

        $this->assertNotNull($model);
    }

    public function testModelPersists(): void
    {
        $model = new TestModel([
            'data' => 'test value',
        ]);

        $this->assertTrue($model->save());
    }

    public function testManagerLoads(): void
    {
        $driver = Forward::handler();

        $this->assertInstanceOf('Lester\Forwarding\Contracts\CallForwardingDriver', $driver);
    }

    public function testModelDoesNotPersistAndIsQueued(): void
    {
        $model = new TestModel([
            'data' => 'test value',
        ]);

        $this->assertFalse($model->forwarded()->save());

        $files = $model->callForwardingGetQueue('insert');

        $this->assertInstanceOf('Illuminate\Support\Collection', $files);
        $this->assertGreaterThan(0, $files->count());

    }

    public function testUpdates(): void
    {
        $model = new TestModel([
            'data' => 'test value',
        ]);

        $this->assertTrue($model->save());
        $this->assertGreaterThan(0, $model->id);
        $result = $model->forwarded()->update([
            'data' => 'saved',
        ]);
        $this->assertFalse($result);

        $this->assertGreaterThan(0, $model->callForwardingTransitionUpdates());

    }
}
