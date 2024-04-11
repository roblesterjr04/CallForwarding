<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Lester\Forwarding\ReceivesCalls;
use Lester\Forwarding\ShouldForward;

class TestModel extends Model implements ShouldForward
{
	protected $fillable = [
		'data',
	];
	
	use ReceivesCalls;
}