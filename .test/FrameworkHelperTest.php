<?php

class FrameworkHelperTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::setup( function () {
    } );

    parent::__construct( $name, $data, $dataName );
  }

  public function testString() {

    // test unqiue string generation length
    $this->assertEquals( 542, strlen( Framework\Helper\String::unique( 542 ) ) );
    
    // TODO test uncovered String and File method
  }
}
