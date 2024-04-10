<?php

namespace Lester\CallForwarding\Facades;

use Illuminate\Support\Facades\Facade;

class Forward extends Facade
{
	/* public static function fake($jobsToFake = [])
	{
		static::swap($fake = new SObjectsFake(static::getFacadeRoot()));

		return $fake;
	} */

	protected static function getFacadeAccessor()
	{
		return 'forward';
	}
}
