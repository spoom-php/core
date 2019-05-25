<?php require __DIR__ . '/../vendor/autoload.php';

use Spoom\Core\File;
use Spoom\Core\Environment;

// setup the Spoom environment
$spoom = new Environment(
  Environment::TEST,
  ( $tmp = new File( __DIR__ ) )
);
