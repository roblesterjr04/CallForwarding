<?php

return [
	
	'handler' => env('CF_DRIVER', 'file'),
		
	'handlers' => [
		'redis' => \Lester\Forwarding\Handlers\RedisHandler::class,
		'file' => \Lester\Forwarding\Handlers\FileHandler::class,
	],
	
	'file_path' => env('CF_FILE_PATH','/tmp/cf'),
	'redis_connection' => env('CF_REDIS_CONN', 'default'),
	
	'frequency' => env('CF_FREQUENCY', 'everyMinute'),
	
];