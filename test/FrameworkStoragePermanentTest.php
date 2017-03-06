<?php namespace Spoom\Framework;

use PHPUnit\Framework\TestCase;
use Spoom\Framework\Converter;
use Spoom\Framework\File;

class FrameworkStoragePermanentTest extends TestCase {

  const TEST_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'FrameworkStoragePermanentTest' . DIRECTORY_SEPARATOR;

  public static function setUpBeforeClass() {

    // create test files
    @copy(
      self::TEST_DIRECTORY . 'test/sample.xml',
      self::TEST_DIRECTORY . 'test/test-xml.xml'
    );
    @copy(
      self::TEST_DIRECTORY . 'test/sample.json',
      self::TEST_DIRECTORY . 'test/test-json.json'
    );
    @copy(
      self::TEST_DIRECTORY . 'test/sample.ini',
      self::TEST_DIRECTORY . 'test/test-ini.ini'
    );
    @copy(
      self::TEST_DIRECTORY . 'test/sample.pser',
      self::TEST_DIRECTORY . 'test/test-native.pser'
    );
  }
  public static function tearDownAfterClass() {

    // remove all generated files
    @unlink( self::TEST_DIRECTORY . 'test.json' );
    @unlink( self::TEST_DIRECTORY . 'test.xml' );

    @unlink( self::TEST_DIRECTORY . 'test/test-json.json' );
    @unlink( self::TEST_DIRECTORY . 'test/test-xml.xml' );
    @unlink( self::TEST_DIRECTORY . 'test/test-ini.ini' );
    @unlink( self::TEST_DIRECTORY . 'test/test-native.pser' );
    @unlink( self::TEST_DIRECTORY . 'test/test-json.pser' );
  }

  /**
   * @throws Exception\Runtime
   */
  public function testFile() {

    $storage = $this->getStorage( '', 'test' );
    $storage->set( 'test1.test2.test3', 3 );
    $storage->set( 'test1.test4', 4 );

    // test file saving
    $storage->save();
    $this->assertFileExists( self::TEST_DIRECTORY . 'test.json' );
    $this->assertJsonFileEqualsJsonFile(
      self::TEST_DIRECTORY . 'test-sample.json',
      self::TEST_DIRECTORY . 'test.json'
    );

    // test file extension change
    $storage->save( null, 'xml' );
    $this->assertFileExists( self::TEST_DIRECTORY . 'test.xml' );
    $this->assertFileNotExists( self::TEST_DIRECTORY . 'test.json' );

    // test autoloader off
    $storage       = $this->getStorage( '', 'test' );
    $storage->auto = false;
    $this->assertEquals( null, $storage->get( 'test1.test2.test3' ) );

    // test loading
    $storage->auto = true;
    $this->assertEquals( 3, $storage->get( 'test1.test2.test3' ) );

    // test file destroy
    $storage->remove();
    $this->assertFileNotExists( self::TEST_DIRECTORY . 'test.xml' );
  }
  /**
   * @throws Exception\Runtime
   */
  public function testDirectory() {

    // test all native format unserializer
    $storage = $this->getStorage( 'test/' );
    $this->assertEquals( 3, $storage->get( 'test-json:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'test-ini:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'test-xml:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'test-native:test1.test2.test3' ) );

    // write out all data again to check the serialize
    $storage->save( 'test-json', 'json' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertJsonFileEqualsJsonFile(
      self::TEST_DIRECTORY . 'test/sample.json',
      self::TEST_DIRECTORY . 'test/test-json.json'
    );
    $storage->save( 'test-xml', 'xml' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertXmlFileEqualsXmlFile(
      self::TEST_DIRECTORY . 'test/sample.xml',
      self::TEST_DIRECTORY . 'test/test-xml.xml'
    );
    $storage->save( 'test-ini', 'ini' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertEquals(
      parse_ini_file( self::TEST_DIRECTORY . 'test/sample.ini' ),
      parse_ini_file( self::TEST_DIRECTORY . 'test/test-ini.ini' )
    );
    $storage->save( 'test-native', 'pser' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertEquals(
      trim( file_get_contents( self::TEST_DIRECTORY . 'test/sample.pser' ) ),
      trim( file_get_contents( self::TEST_DIRECTORY . 'test/test-native.pser' ) )
    );

    // try the namespace remove
    $storage->remove( 'test-native' );
    $this->assertEquals( null, $storage->get( 'test-native:test1.test2.test3' ) );
    $this->assertFileNotExists( self::TEST_DIRECTORY . 'test/test-native.pser' );

    // try namespace format change
    $storage->save( 'test-json', 'pser' );
    $this->assertEquals( 3, $storage->get( 'test-json:test1.test2.test3' ) );
    $this->assertFileNotExists( self::TEST_DIRECTORY . 'test/test-json.json' );
    $this->assertFileExists( self::TEST_DIRECTORY . 'test/test-json.pser' );
  }

  private function getStorage( $path, $file = null ) {
    $storage = new Storage\File( ( new File\System( self::TEST_DIRECTORY ) )->get( $path ), [
      new Converter\Json(),
      new Converter\Xml(),
      new Converter\Ini(),
      new Converter\Native()
    ], $file );

    return $storage;
  }
}
