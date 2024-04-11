<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Lester\CallForwarding\Concerns\ReceivesCalls;
use Lester\CallForwarding\Contracts\ShouldForward;

class TestModel extends Model implements ShouldForward
{
	protected $fillable = [
		'data',
	];
	
	use ReceivesCalls;
}