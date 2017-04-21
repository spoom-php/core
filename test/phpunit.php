<?php require '../vendor/autoload.php';

use Spoom\Core\Application;
use Spoom\Core\File;

// setup the Spoom application 
$spoom = new Application(
  Application::ENVIRONMENT_TEST,
  'en',
  ( $tmp = new File( __DIR__ ) )
);
