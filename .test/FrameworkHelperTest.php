<?php

use Framework\Helper\Number;
use Framework\Helper;

class FrameworkHelperTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [], $dataName = '' ) {
    \Framework::setup( \Framework::ENVIRONMENT_DEVELOPMENT ) && \Framework::execute( function () { } );

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

  public function testConverter() {

    $list = new Helper\Converter( [
      new Helper\Converter\Json(),
      new Helper\Converter\Ini()
    ] );

    // basic converter tests
    $this->assertEquals( 2, count( $list->get() ) );
    $this->assertTrue( $list->get( Helper\Converter\Json::FORMAT ) instanceof Helper\Converter\Json );
    $this->assertEquals( 512, $list->get( Helper\Converter\Json::FORMAT )->getMeta()->depth );

    // test converter overwrite
    $list->add( new Helper\Converter\Json( 0, 256 ), false );
    $this->assertEquals( 512, $list->get( Helper\Converter\Json::FORMAT )->getMeta()->depth );
    $list->add( new Helper\Converter\Json( 0, 256 ), true );
    $this->assertEquals( 256, $list->get( Helper\Converter\Json::FORMAT )->getMeta()->depth );

    // test converter remove
    $list->remove( Helper\Converter\Json::FORMAT );
    $this->assertNull( $list->get( Helper\Converter\Json::FORMAT ) );
  }
  /**
   * @dataProvider providerConverter
   *
   * @param Helper\ConverterInterface $converter
   */
  public function testConverterType( Helper\ConverterInterface $converter ) {

    $content = (object) [ 'test1' => (object) [ 'test2' => (object) [ 'test3' => 3 ], 'test4' => 4 ] ];
    $tmp     = $converter->serialize( $content );
    $this->assertNull( $converter->getException() );
    $this->assertEquals( $content, $converter->unserialize( $tmp ) );

    // check for stream support
    $content = (object) [ 'test1' => (object) [ 'test2' => (object) [ 'test3' => 3 ], 'test4' => 4 ] ];
    $tmp     = fopen( 'php://memory', 'w+' );

    $converter->serialize( $content, $tmp );
    fseek( $tmp, 0 );
    $this->assertNull( $converter->getException() );
    $this->assertEquals( $content, $converter->unserialize( $tmp ) );
  }
  public function testConverterTypeIni() {
    // TODO add detailed converter tests
  }
  public function testConverterTypeXml() {
    // TODO add detailed converter tests
  }

  public function providerConverter() {
    return [
      [ new Helper\Converter\Json() ],
      [ new Helper\Converter\Ini() ],
      [ new Helper\Converter\Xml() ],
      [ new Helper\Converter\Native() ]
    ];
  }
}
