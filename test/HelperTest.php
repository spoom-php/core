<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;
use Spoom\Core\Helper;

class HelperTest extends TestCase {

  private static $directory;

  public static function setUpBeforeClass() {

    // reset stream test files
    static::$directory = __DIR__ . '/HelperTest/';
    @file_put_contents( static::$directory . 'stream-a.txt', '01234' );
    @file_put_contents( static::$directory . 'stream-r.txt', '01234' );
  }
  public static function tearDownAfterClass() {

    // clear stream test files
    @unlink( static::$directory . 'stream-rw.txt' );
  }

  public function testText() {

    // test unqiue string generation length
    $this->assertEquals( 542, strlen( Helper\Text::unique( 542 ) ) );

    // test context insertion
    $this->assertEquals( 'This is bar', Helper\Text::apply( 'This is {foo}', [ 'foo' => 'bar' ] ) );
    $this->assertEquals( 'This is "{foo}"', Helper\Text::apply( 'This is "{foo}"', [ 'foo' => 'bar' ], '"\'' ) );
    $this->assertEquals( 'This is 1 foo and 2 bar', Helper\Text::apply( 'This is {foo} foo and {bar} bar', [], '', function ( $name ) {
      return $name == 'foo' ? 1 : 2;
    } ) );

    // TODO test uncovered String methods
  }
  public function testCollectionBasic() {

    $this->assertTrue( Helper\Collection::is( new Storage( [] ), true, true ) );
    $this->assertTrue( Helper\Collection::is( new \StdClass(), true ) );
    $this->assertFalse( Helper\Collection::is( new \StdClass(), true, true ) );

    $this->assertTrue( Helper\Collection::isArrayNumeric( [ 'a', 'b', 'c' ] ) );
    $this->assertTrue( Helper\Collection::isArrayNumeric( [ 'a', 'b', 'c' ], false ) );
    $this->assertTrue( Helper\Collection::isArrayNumeric( [ 1 => 'a', 'b', 'c' ], false ) );
    $this->assertTrue( Helper\Collection::isArrayNumeric( [ '0' => 'a', 'b', 'c' ] ) );
    $this->assertFalse( Helper\Collection::isArrayNumeric( [ 'a' => 'a', 'b', 'c' ] ) );

    // TODO test read
  }
  public function testCollectionExtra() {

    // test iterable merging
    $a = [ 'a' => 'a', 'b' => [ 'c' => [ 'e' => 'e' ] ] ];
    $b = [ 'd' => 'd', 'b' => [ 'f' => 'f' ] ];
    $this->assertEquals( array_merge_recursive( $a, $b ), Helper\Collection::merge( $a, $b ) );
    $this->assertEquals( array_merge( $a, $b ), Helper\Collection::merge( $a, $b, false ) );

    // 
    $c          = [ 'b' => [ 'c' => 'c' ] ];
    $tmp        = $a;
    $tmp[ 'b' ] = $c[ 'b' ];
    $this->assertEquals( $tmp, Helper\Collection::merge( $a, $c ) );

    // test input type keeping and storage support (of merging)
    $tmp = new Storage( $a );
    $this->assertTrue( $tmp === Helper\Collection::merge( $tmp, $b ) );
    $this->assertEquals( array_merge_recursive( $a, $b ), Helper\Collection::read( Helper\Collection::merge( $tmp, $b ), [], true ) );

    // TODO test copy
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
   * @dataProvider providerStructure
   *
   * @param Helper\Structure $io
   * @param array            $expect
   * @param                  $input
   */
  public function testStructure( $io, array $expect, $input ) {

    $tmp = $io::instance( $input );
    $this->assertEquals( $expect, Helper\Collection::read( $tmp, null, true ) );
  }

  /**
   * Test stream functionalities
   */
  public function testStream() {

    // test empty writeable stream
    $rw = new Helper\Stream( static::$directory . 'stream-rw.txt', Helper\StreamInterface::MODE_RW );
    $this->assertEquals( 0, $rw->getSize() );
    $this->assertTrue( $rw->isReadable() && $rw->isWritable() && $rw->isSeekable() );

    // test basic read/write operations
    $rw->write( '0123--6789' );
    $this->assertEquals( 10, $rw->getSize() );
    $rw->seek( 4 );
    $this->assertEquals( 4, $rw->getOffset() );
    $rw->write( '45' );
    $this->assertEquals( '0123456789', $rw->read( 0, 0 ) );

    // test write from stream
    $a = new Helper\Stream( static::$directory . 'stream-a.txt', Helper\StreamInterface::MODE_RWA );
    $this->assertTrue( $a->isWritable() && $a->isReadable() && $a->isSeekable() );

    $a->write( $rw->seek( 0 ) );
    $this->assertEquals( 15, $a->getSize() );
    $this->assertEquals( '012340123456789', $a->read( 0, 0 ) );

    // test read only and read to stream
    $r = new Helper\Stream( static::$directory . 'stream-r.txt', Helper\StreamInterface::MODE_READ );
    $this->assertTrue( !$r->isWritable() && $r->isReadable() && $r->isSeekable() );

    $this->assertEquals( '01234', $r->read( 0, 0 ) );
    $r->read( 0, 0, $rw->seek( 5 ) );
    $this->assertEquals( '0123401234', $rw->read( 0, 0 ) );

    // test size check
    // TODO test with different streams
    $this->assertEquals( 5, $r->getSize() );
    $this->assertEquals( 3, $r->seek( 2 )->getSize( true ) );
    $this->assertEquals( 5, $r->getSize() );
  }

  /**
   * @dataProvider providerConverter
   *
   * @param ConverterInterface $converter
   *
   * @depends      testStream
   */
  public function testConverterType( ConverterInterface $converter ) {

    $content = (object) [ 'test1' => (object) [ 'test2' => (object) [ 'test3' => 3 ], 'test4' => 4 ] ];
    $tmp     = $converter->serialize( $content );
    $this->assertEquals( $content, $converter->unserialize( $tmp ) );

    // check for stream support
    $content = (object) [ 'test1' => (object) [ 'test2' => (object) [ 'test3' => 3 ], 'test4' => 4 ] ];
    $tmp     = Helper\Stream::instance( fopen( 'php://memory', 'w+' ) );

    $converter->serialize( $content, $tmp );
    $tmp->seek( 0 );
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
  public function providerStructure() {
    // Structure, expect, input
    return [
      [ HelperTestStructure1::class, [
        'test00' => '00',
        'test01' => '0',
        'test11' => '11',
        'test22' => 2,
        'test4'  => [ 'test1' => 'test' ],
        'test5'  => [
          'a' => [ 'test1' => 'testa' ],
          'b' => [ 'test1' => 'testb' ]
        ]
      ], [
        'test0' => '0',
        'test1' => [
          'test11' => '11'
        ],
        'test2' => '2',
        'test4' => [ 'test' => 'test' ],
        'test5' => [
          'a' => [ 'test' => 'testa' ],
          'b' => [ 'test' => 'testb' ]
        ]
      ] ]
    ];
  }
}

class HelperTestStructure1 extends Helper\Structure {

  const PROPERTY_MAP  = [
    // simple mapping
    'test0'        => 'test01',
    // deep mapping
    'test1.test11' => 'test11',

    // simple map with type forcing
    'test2!int'    => 'test22'
  ];
  const PROPERTY_WRAP = [
    // wrap in a class
    'test4' => HelperTestStructure2::class,
    // wrap every subelement in a class
    'test5' => '[]' . HelperTestStructure2::class
  ] + parent::PROPERTY_WRAP;

  // it must be remain untouched
  public $test00 = '00';

  public $test01;
  public $test11;

  public $test22;

  public $test4;
  public $test5;
}
class HelperTestStructure2 extends Helper\Structure {

  const PROPERTY_MAP = [
    'test' => 'test1'
  ];

  public $test1;
}
