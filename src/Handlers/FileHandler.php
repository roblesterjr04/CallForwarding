<?php

namespace Lester\Forwarding\Handlers;

use Lester\Forwarding\CallManager;
use Lester\Forwarding\Contracts\CallForwardingDriver;
use Illuminate\Support\Collection;

class FileHandler extends CallManager implements CallForwardingDriver
{
	public function putItem($key, $data): void
	{
		$subKey = md5($data);
		$dir = $this->root() . '/' . $key;
		
		if (!file_exists($dir)) {
			mkdir($dir);
		}
		
		$path = $this->root() . '/' . $key . '/' . $subKey;
		
		file_put_contents($path, $data);
			
	}
	
	public function getAllItems($key): Collection
	{
		$dir = $this->root() . '/' . $key;
		
		$members = scandir($dir);
		
		return collect(array_map(function ($member) use ($dir) {
			try {
				$member = $dir . '/' . $member;
				$content = file_get_contents($member);
				unlink($member);
			
				return json_decode($content, true);
			} catch (\Exception $e) {
				return null;
			}
		}, $members))->filter(function($item) {
			return $item !== null;
		});
	}
	
	private function root()
	{
		return $this->normalPath(config('call-forwarding.file_path'));
	}
	
	private function normalPath($path)
	{
		$parts = explode("/", $path);
		return '/' . implode('/', array_filter($parts, function($part) {
			return $part != "";
		}));
	}
}