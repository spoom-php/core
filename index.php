<?php require 'framework.php';

// execute the framework with the callbacks
Framework::execute( function () {

  // execute the 'framework' extension's request handler
  Framework\Request::execute( \Framework\Request::ENVIRONMENT_PRODUCTION );

}, '\Framework\Request::terminate', '\Framework\Request::failure' );
