# Laravel Call Forwarding

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rob-lester-jr04/call-forwarding.svg)](https://packagist.org/packages/rob-lester-jr04/call-forwarding)
[![PHP Composer](https://github.com/roblesterjr04/CallForwarding/actions/workflows/test-suite.yml/badge.svg)](https://github.com/roblesterjr04/CallForwarding/actions/workflows/test-suite.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/rob-lester-jr04/call-forwarding.svg)](https://packagist.org/packages/rob-lester-jr04/call-forwarding)

Our innovative "CallForwarding" package optimizes database performance during high-traffic scenarios by intelligently queuing up database writes on eloquent models. By consolidating these writes into a single operation, it alleviates strain on database resources, ensuring smooth operations even during peak times. With ForwardSync, your system gains efficiency and resilience, allowing you to focus on delivering exceptional user experiences without worrying about database constraints.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Support](#support)
- [Contributing](#contributing)

## Installation

Download to your project directory, add `README.md`, and commit:

```sh
composer require rob-lester-jr04/call-forwarding
```

## Usage

For each model you want to implement call forwarding on, include both the trait and the interface.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lester\Forwarding\ReceivesCalls;
use Lester\Forwarding\ShouldForward;

class PageVisit extends Model implements ShouldForward
{
	
	use ReceivesCalls;
	
}

```

Then, to make an update or an insert, call `callForwarding()` before your persist method. Example:

```php
$visit = new PageVisit;
$visit->hits = 15;

$visit-> forwarded()->save(); // This will return false. That is expected.
```

Now by default, every minute, anything that has been queued this way will get persisted.

## Advanced Configuration

### Changing the driver

The package is built in with 2 drivers for the queue. The default is `file`, but it also includes `redis`.

Your choices out of the box are File or Redis. To use the Redis option, add this to your `.env` file:

```env
CF_DRIVER=redis
```

### Write Callbacks

If you have code you'd like to execute after the write has occured, you can chain the `afterForward` method with a closure as the parameter. Example.

```php

$model = new PageVisit;

$visit->afterForward(function($attrs) {
	// Do something else with the attributes.
})->forwarded()->save();

```

### Publishing the config file.

```sh
php artisan vendor:publish --provider="Lester\Forwarding\CallForwardingServiceProvider"
```

### Custom Driver

Too generate your own driver, create a class that implements the `CallForwardingDriver` interface with the following methods: `putItem`, `getAllItems`

Register the custom class in the config file under the handler array and use the slug you defined as the handler.

```php

'handler' => 'my-custom-handler',

'handlers' => [
	// ...
	'my-custom-handler' => \App\My\Handler::class,
], 
```

```php
<?php

namespace App\Handlers; // or whatever namespace you want to use

use Lester\Forwarding\Contracts\CallForwardingDriver;
use Illuminate\Support\Collection;

class MyHandler implements CallForwardingDriver
{
	// Worth noting, $key is only ever 'update' or 'insert'
	public function putItem($key, $data): void
	{
		// Store the db write.
	}
	
	public function getAllItems($key, $purge = false): Collection
	{
		// Retrieve the collection of stored db writes
		
		// Purge each item if the $purge flag is true.
	}
}

```

## Support

Please [open an issue](https://github.com/fraction/readme-boilerplate/issues/new) for support.

## Contributing

Please contribute using [Github Flow](https://guides.github.com/introduction/flow/). Create a branch, add commits, and [open a pull request](https://github.com/fraction/readme-boilerplate/compare/).