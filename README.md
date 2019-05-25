[![Build Status](https://travis-ci.org/spoom-php/core.svg?branch=development)](https://travis-ci.org/spoom-php/core)

# Spoom Packages
Spoom is a collection of cooperative packages, which you can use to code your Application of needs.

## About the Core
This is the core package which provide:

 * Extended exception capabilities with string id and context support
 * File access interface and local filesystem implementation
 * Logger interface with file logger implementation
 * Data serialization and deserialization interface (with serialize and json implementation)
 * Helpers to solve basic task and some PHP inconsistency
 * Event and package (Spoom packages) management
 * ...

## Installation
Install the latest version with

```bash
$ composer require spoom-php/core
```

## Requirements
Spoom Core (and most official package) works with PHP 7.2 or above

## Usage
Here is a basic example to initialize the `Core\Environment`:

```php
<?php require __DIR__ . '/vendor/autoload.php';

use Spoom\Core\Environment;
use Spoom\Core\File;

// create an environment (if you wish, it's also optional in most cases) to store some globals for your application. Storing it in a variable is
// optional, you can access the application anywhere using the `Environment::instance()` static method
$spoom = new Environment(

  // used environment's name
  Environment::DEVELOPMENT,

  // root directory of the application
  new File( __DIR__ )
);

// do something fancy..
```

## License
The Spoom is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
