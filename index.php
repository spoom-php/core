<?php

$framework = new Framework\Application( __DIR__, [
  'environment'  => Framework\Application::ENVIRONMENT_PRODUCTION,
  'log_level'    => \Framework\Application::LEVEL_INFO,
  'report_level' => \Framework\Application::LEVEL_NONE,

  'localization' => 'en',
  'locale'       => null,
  'encoding'     => mb_internal_encoding(),
  'timezone'     => date_default_timezone_get()
] );
