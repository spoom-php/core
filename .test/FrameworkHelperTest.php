<?php

use Framework\Helper\Number;

class FrameworkHelperTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::execute( function () {
    } );

    parent::__construct( $name, $data, $dataName );
  }

  public function testString() {

    // test unqiue string generation length
    $this->assertEquals( 542, strlen( Framework\Helper\Text::unique( 542 ) ) );
    
    // TODO test uncovered String and File method
  }
  public function testNumber() {

    $this->assertTrue( Number::is( 123456789 ) );
    $this->assertTrue( Number::is( 1.23456789 ) );
    $this->assertTrue( Number::is( "123456789" ) );
    $this->assertTrue( Number::is( "1.23456789" ) );
    $this->assertTrue( Number::is( "1,23456789" ) );
    $this->assertFalse( Number::is( "1,23456789a" ) );
    $this->assertFalse( Number::is( " 1,23456789" ) );
    $this->assertFalse( Number::is( [ 'a' ] ) );

    $this->assertTrue( Number::isInteger( 123456789 ) );
    $this->assertFalse( Number::isInteger( 1.23456789 ) );
    $this->assertTrue( Number::isInteger( "123456789" ) );
    $this->assertFalse( Number::isInteger( "1.23456789" ) );
    $this->assertFalse( Number::isInteger( [ 'a' ] ) );

    $this->assertFalse( Number::isReal( 123456789 ) );
    $this->assertTrue( Number::isReal( 1.23456789 ) );
    $this->assertFalse( Number::isReal( "123456789" ) );
    $this->assertTrue( Number::isReal( "1.23456789" ) );
    $this->assertFalse( Number::isReal( [ 'a' ] ) );

    $this->assertEquals( "123456789", Number::write( 123456789 ) );
    $this->assertEquals( "1.23456789", Number::write( 1.23456789 ) );
    $this->assertEquals( "123456789.000", Number::write( 123456789, 3 ) );
    $this->assertEquals( "1.2346", Number::write( 1.23456789, 4 ) );
    $this->assertEquals( [ 'a' ], Number::write( [ 'a' ] ) );

    $this->assertEquals( 123456789, Number::read( "123456789" ) );
    $this->assertEquals( 1.23456789, Number::read( "1.23456789" ) );
    $this->assertEquals( 1.23456789, Number::read( "1,23456789" ) );
    $this->assertEquals( 'a', Number::read( [ 'a' ], 'a' ) );

    $this->assertTrue( Number::equal( "123456789", 123456789 ) );
    $this->assertTrue( Number::equal( "123.456789", 123.4568, 4 ) );
    $this->assertTrue( Number::equal( 123.6, 123.62 ) );
    $this->assertTrue( Number::equal( 123.3, 123 ) );
  }
}
