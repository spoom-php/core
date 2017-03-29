<?php namespace Spoom\Framework;

use PHPUnit\Framework\TestCase;
use Spoom\Framework\Helper;

class FrameworkHelperTest extends TestCase {

  private static $directory;

  public static function setUpBeforeClass() {

    // reset stream test files
    static::$directory = __DIR__ . '/FrameworkHelperTest/';
    @file_put_contents( static::$directory . 'stream-a.txt', '01234' );
    @file_put_contents( static::$directory . 'stream-r.txt', '01234' );
  }
  public static function tearDownAfterClass() {

    // clear stream test files
    @unlink( static::$directory . 'stream-rw.txt' );
  }

  public function testString() {

    // test unqiue string generation length
    $this->assertEquals( 542, strlen( Helper\Text::unique( 542 ) ) );

    // TODO test uncovered String methods
  }
  public function testNumber() {

    $this->assertTrue( Helper\Number::is( 123456789 ) );
    $this->assertTrue( Helper\Number::is( 1.23456789 ) );
    $this->assertTrue( Helper\Number::is( "123456789" ) );
    $this->assertTrue( Helper\Number::is( "1.23456789" ) );
    $this->assertTrue( Helper\Number::is( "1,23456789" ) );
    $this->assertFalse( Helper\Number::is( "1,23456789a" ) );
    $this->assertFalse( Helper\Number::is( " 1,23456789" ) );
    $this->assertFalse( Helper\Number::is( [ 'a' ] ) );

    $this->assertTrue( Helper\Number::isInteger( 123456789 ) );
    $this->assertFalse( Helper\Number::isInteger( 1.23456789 ) );
    $this->assertTrue( Helper\Number::isInteger( "123456789" ) );
    $this->assertFalse( Helper\Number::isInteger( "1.23456789" ) );
    $this->assertFalse( Helper\Number::isInteger( [ 'a' ] ) );

    $this->assertFalse( Helper\Number::isReal( 123456789 ) );
    $this->assertTrue( Helper\Number::isReal( 1.23456789 ) );
    $this->assertFalse( Helper\Number::isReal( "123456789" ) );
    $this->assertTrue( Helper\Number::isReal( "1.23456789" ) );
    $this->assertFalse( Helper\Number::isReal( [ 'a' ] ) );

    $this->assertEquals( "123456789", Helper\Number::write( 123456789 ) );
    $this->assertEquals( "1.23456789", Helper\Number::write( 1.23456789 ) );
    $this->assertEquals( "123456789.000", Helper\Number::write( 123456789, 3 ) );
    $this->assertEquals( "1.2346", Helper\Number::write( 1.23456789, 4 ) );
    $this->assertEquals( [ 'a' ], Helper\Number::write( [ 'a' ], null, [ 'a' ] ) );

    $this->assertEquals( 123456789, Helper\Number::read( "123456789" ) );
    $this->assertEquals( 1.23456789, Helper\Number::read( "1.23456789" ) );
    $this->assertEquals( 1.23456789, Helper\Number::read( "1,23456789" ) );
    $this->assertEquals( -1, Helper\Number::read( [ 'a' ], -1 ) );

    $this->assertTrue( Helper\Number::equal( "123456789", 123456789 ) );
    $this->assertTrue( Helper\Number::equal( "123.456789", 123.4568, 4 ) );
    $this->assertTrue( Helper\Number::equal( 123.6, 123.62 ) );
    $this->assertTrue( Helper\Number::equal( 123.3, 123 ) );
  }

  /**
   * Test stream functionalities
   */
  public function testStream() {

    // test empty writeable stream
    $rw = new Helper\Stream( static::$directory . 'stream-rw.txt', Helper\StreamInterface::MODE_RW );
    $this->assertEquals( 0, $rw->count() );
    $this->assertTrue( $rw->isReadable() && $rw->isWritable() && $rw->isSeekable() );

    // test basic read/write operations
    $rw->write( '0123--6789' );
    $this->assertEquals( 10, $rw->count() );
    $rw->seek( 4 );
    $this->assertEquals( 4, $rw->getOffset() );
    $rw->write( '45' );
    $this->assertEquals( '0123456789', $rw->read( 0, 0 ) );

    // test write from stream
    $a = new Helper\Stream( static::$directory . 'stream-a.txt', Helper\StreamInterface::MODE_RWA );
    $this->assertTrue( $a->isWritable() && $a->isReadable() && $a->isSeekable() );

    $a->write( $rw->seek( 0 ) );
    $this->assertEquals( 15, $a->count() );
    $this->assertEquals( '012340123456789', $a->read( 0, 0 ) );

    // test read only and read to stream
    $r = new Helper\Stream( static::$directory . 'stream-r.txt', Helper\StreamInterface::MODE_READ );
    $this->assertTrue( !$r->isWritable() && $r->isReadable() && $r->isSeekable() );

    $this->assertEquals( '01234', $r->read( 0, 0 ) );
    $r->read( 0, 0, $rw->seek( 5 ) );
    $this->assertEquals( '0123401234', $rw->read( 0, 0 ) );
  }

  public function testConverter() {

    $list = new ConverterMap( [
      $converter_json = new Converter\Json(),
      $converter_ini = new Converter\Ini()
    ] );

    // basic converter tests
    $this->assertEquals( 2, count( $list->get() ) );
    $this->assertTrue( $list->get( Converter\Json::FORMAT ) instanceof Converter\Json );
    $this->assertEquals( 512, $list->get( Converter\Json::FORMAT )->getMeta()->depth );

    // test converter overwrite
    $list->add( new Converter\Json( 0, 256 ), false );
    $this->assertEquals( 512, $list->get( Converter\Json::FORMAT )->getMeta()->depth );
    $list->add( new Converter\Json( 0, 256 ), true );
    $this->assertEquals( 256, $list->get( Converter\Json::FORMAT )->getMeta()->depth );

    // test converter remove
    $list->remove( Converter\Json::FORMAT );
    $this->assertNull( $list->get( Converter\Json::FORMAT ) );

    // test for format mapping
    $list->add( $converter_json );
    $list->setMap( [
      'format-test0' => Converter\Json::FORMAT,
      'format-test1' => Converter\Json::FORMAT
    ] );

    $this->assertEquals( $converter_json, $list->get( 'format-test0' ) );
    $this->assertEquals( $converter_json, $list->get( Converter\Json::FORMAT ) );
    $this->assertNull( $list->get( 'format-test2' ) );
  }
  /**
   * @dataProvider providerConverter
   *
   * @param ConverterInterface $converter
   *
   * @depends      testConverter
   * @depends      testStream
   */
  public function testConverterType( ConverterInterface $converter ) {

    $content = (object) [ 'test1' => (object) [ 'test2' => (object) [ 'test3' => 3 ], 'test4' => 4 ] ];
    $tmp     = $converter->serialize( $content );
    $this->assertNull( $converter->getException() );
    $this->assertEquals( $content, $converter->unserialize( $tmp ) );

    // check for stream support
    $content = (object) [ 'test1' => (object) [ 'test2' => (object) [ 'test3' => 3 ], 'test4' => 4 ] ];
    $tmp = Helper\Stream::instance( fopen( 'php://memory', 'w+' ) );

    $converter->serialize( $content, $tmp );
    $tmp->seek( 0 );
    $this->assertNull( $converter->getException() );
    $this->assertEquals( $content, $converter->unserialize( $tmp ) );
  }
  public function testConverterTypeIni() {
    // TODO add detailed converter tests
    $this->assertTrue( true );
  }
  public function testConverterTypeXml() {
    // TODO add detailed converter tests
    $this->assertTrue( true );
  }

  public function providerConverter() {
    return [
      [ new Converter\Json() ],
      [ new Converter\Ini() ],
      [ new Converter\Xml() ],
      [ new Converter\Native() ]
    ];
  }
}
