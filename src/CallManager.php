<?php

namespace Lester\Forwarding;

class CallManager
{
	public function handler()
	{
		$handlerSlug = config('call-forwarding.handler');
		$handler = config('call-forwarding.handlers.'.$handlerSlug);
		return app()->make($handler);
	}
}