<?php require '../vendor/autoload.php';

use Spoom\Framework\Application;

$spoom = new Application( __DIR__, [
  'environment'  => Application::ENVIRONMENT_DEVELOPMENT,
  'log_level'    => Application::LEVEL_DEBUG,
  'report_level' => Application::LEVEL_DEBUG,

  'localization' => 'en',
  'locale'       => null,
  'encoding'     => mb_internal_encoding(),
  'timezone'     => date_default_timezone_get()
] );
