<?php

namespace Lester\Forwarding;

use Lester\Forwarding\ShouldForward;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class CallForwardingServiceProvider extends ServiceProvider
{
	const CONFIG_PATH = __DIR__ . '/../config/call-forwarding.php';
	/**
	 * Register services.
	 */
	public function register(): void
	{
		$this->mergeConfigFrom(
			self::CONFIG_PATH,
			'call-forwarding'
		);
		
		$this->app->bind('forward', function() {
			return new CallManager();
		});
		
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Forward', 'Lester\Forwarding\Facades\Forward');
	}

	/**
	 * Bootstrap services.
	 */
	public function boot(): void
	{
		$this->publishes([
			self::CONFIG_PATH => config_path('call-forwarding.php'),
		], 'config');
		
		$this->app->booted(function () {
			$frequency = config('call-forwarding.frequency');
			$schedule = $this->app->make(Schedule::class);
			$schedule->call(function () {
				foreach ($this->findForwardingModels() as $forwardClass) {
					$call = $this->app->make($forwardClass);
					$call->forwardCallsToInsert();
					$call->forwardCallsToUpdate();
				}
			})->$frequency();
		});
	}

	public function findForwardingModels()
	{
		$implementations = [];

		foreach ($this->getForwardingModels() as $class) {
			// Check if the class implements the interface
			$reflectionClass = new \ReflectionClass($class);
			if ($reflectionClass->implementsInterface(ShouldForward::class)) {
				$implementations[] = $class;
			}
		}

		return $implementations;
	}

	public function getForwardingModels(): Collection
	{
		$models = collect(File::allFiles(app_path()))
			->map(function ($item) {
				$path = $item->getRelativePathName();
				$class = sprintf('\%s%s',
					Container::getInstance()->getNamespace(),
					strtr(substr($path, 0, strrpos($path, '.')), '/', '\\'));

				return $class;
			})
			->filter(function ($class) {
				$valid = false;

				if (class_exists($class)) {
					$reflection = new \ReflectionClass($class);
					$valid = $reflection->isSubclassOf(Model::class) &&
						! $reflection->isAbstract();
				}

				return $valid;
			});

		return $models->values();
	}
}
