<?php

class FrameworkHelperTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::setup( function () {
    } );

    parent::__construct( $name, $data, $dataName );
  }

  // TODO test uncovered String and File method
}
