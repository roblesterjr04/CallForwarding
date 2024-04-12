<?php

namespace Tests;

use Tests\Fixtures\TestModel;

class CallbackTest extends TestCase
{
    public function testModelCallbacksWhenupdated(): void
    {
        $model = new TestModel([
            'data' => 'empty',
        ]);

        $this->assertTrue($model->save());

        $result = $model->afterForward(function ($attributes) {

        })->forwarded()->update([
            'data' => 'test',
        ]);

        $this->assertFalse($result);

        $this->assertGreaterThan(0, $model->callForwardingTransitionUpdates());

    }

    public function testModelCallbacksWhenInserted(): void
    {
        $model = new TestModel([
            'data' => 'empty',
        ]);

        $this->assertNotNull($model);

        $model->afterForward(function ($attributes) {

        })->forwarded()->save();

        $this->assertTrue($model->callForwardingTransitionInserts());
    }
}
