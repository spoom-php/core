<?php require 'framework.php';

// setup and execute the framework with the callbacks
Framework::setup() && Framework::execute( function () {

  // execute the 'framework' extension's request handler
  Framework\Request::execute();

}, '\Framework\Request::terminate', '\Framework\Request::failure' );
