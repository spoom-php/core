# Spoom Framework
Spoom is a collection of cooperative libraries (extensions), which you can use to "build" a framework that suits your needs.

## About the Core
This is the core library which provide:

 * Extended exception capabilities with string id and context support
 * File access interface and local filesystem implementation
 * Logger interface with file logger implementation
 * Data serialization and deserialization interface (with json,xml,... implementation)
 * Helpers to solve basic task and some PHP inconsistency
 * Event and extension (Spoom libraries) management
 * ...
 
## Installation
Install the latest version with

```bash
$ composer require spoom-php/core
```

## Requirements
Spoom Core (and most official library) works with PHP 7.1 or above

## Usage
Here is a basic example to initialize the framework with the `Core\Application`:

```php
<?php require 'vendor/autoload.php';

use Spoom\Core\Application;
use Spoom\Core\File;

// this will setup the PHP internal state (optional, but recommended)
Application::environment( Application::SEVERITY_DEBUG, [
  
  // setup LC_ALL value
  'locale' => 'en_US',
  
  // setup default encoding for internal variables and the output
  'encoding' => 'utf8',
  
  // setup default timezone
  'timezone' => 'UTC'
]);

// create an application before you use the Framework. Storing to variable is
// optional, you can access the application anywhere using
// `Application::instance()` static method
$spoom = new Application(
  
  // used environment's name
  Application::ENVIRONMENT_DEVELOPMENT,
                                        
  // default localization
  'en',
  
  // root directory of the application
  new File( __DIR__ )
);

// do something fancy..
```

## License
The Spoom Framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
