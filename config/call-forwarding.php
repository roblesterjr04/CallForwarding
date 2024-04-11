<?php

return [
	
	'handler' => env('CF_DRIVER', 'file'),
		
	'handlers' => [
		'redis' => \Lester\CallForwarding\Handlers\RedisHandler::class,
		'file' => \Lester\CallForwarding\Handlers\FileHandler::class,
	],
	
	'file_path' => env('CF_FILE_PATH','/tmp/cf'),
	'redis_connection' => env('CF_REDIS_CONN', 'default'),
	
];