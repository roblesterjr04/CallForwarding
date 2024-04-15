<?php

namespace Lester\Forwarding\Handlers;

use Illuminate\Support\Collection;
use Lester\Forwarding\CallManager;
use Lester\Forwarding\Contracts\CallForwardingDriver;

class FileHandler extends CallManager implements CallForwardingDriver
{
    public function putItem($key, $data): void
    {
        $subKey = md5($data);
        $dir = $this->root().'/'.$key;

        if (! file_exists($dir) || ! is_dir($dir)) {
            mkdir($dir);
        }

        $path = $this->root().'/'.$key.'/'.$subKey;

        file_put_contents($path, $data);

    }

    public function getAllItems($key, $purge = false): Collection
    {
        $dir = $this->root().'/'.$key;

        $members = scandir($dir);

        return collect(array_map(function ($member) use ($dir, $purge) {
            try {
                $member = $dir.'/'.$member;
                $content = file_get_contents($member);
                if ($purge) unlink($member);

                return json_decode($content, true);
            } catch (\Exception $e) {
                return null;
            }
        }, $members))->filter(function ($item) {
            return $item !== null;
        });
    }

    private function root()
    {
        $path = $this->normalPath(config('call-forwarding.file_path'));
        if (! file_exists($path) || ! is_dir($path)) {
            mkdir($path);
        }

        return $path;
    }

    private function normalPath($path)
    {
        $start = '';
        if (substr($path, 0, 1) === '/') {
            $start = '/';
        }
        $parts = explode('/', $path);

        return $start.implode('/', array_filter($parts, function ($part) {
            return $part != '';
        }));
    }
}
