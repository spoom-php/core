<?php require 'framework.php';

try {

  \Framework::run( function () {
    \Framework\Request::execute();
  } );

} catch( \Exception $e ) {
  die( $e->getMessage() );
}
