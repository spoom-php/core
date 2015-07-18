<?php require 'framework.php';

try {

  // setup the framework and define the main function
  \Framework::setup( function () {

    // execute the 'framework' extension's request handler
    \Framework\Request::execute();

  } );

} catch( \Exception $e ) {
  die( \Framework::reportLevel() < \Framework::LEVEL_CRITICAL ? '' : $e->getMessage() );
}
