<?php

namespace Tests;

use Tests\TestCase;
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
}