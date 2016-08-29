<?php

use Framework\Helper\Converter;

class FrameworkStoragePermanentTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [], $dataName = '' ) {
    \Framework::setup( \Framework::ENVIRONMENT_DEVELOPMENT ) && \Framework::execute( function () { } );

    // create test files
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.xml',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-xml.xml'
    );
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.json',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-json.json'
    );
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.ini',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-ini.ini'
    );
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.pser',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-native.pser'
    );

    parent::__construct( $name, $data, $dataName );
  }
  public function __destruct() {

    // remove all generated files
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.json' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.xml' );

    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-json.json' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-xml.xml' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-ini.ini' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-native.pser' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-json.pser' );
  }

  /**
   * @throws \Framework\Exception\System
   */
  public function testFile() {

    $storage = $this->getStorage( '.test/FrameworkStoragePermanentTest/test' );
    $storage->set( 'test1.test2.test3', 3 );
    $storage->set( 'test1.test4', 4 );

    // test file saving
    $storage->save();
    $this->assertFileExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.json' );
    $this->assertJsonFileEqualsJsonFile(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test-sample.json',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.json'
    );

    // test file extension change
    $storage->save( null, 'xml' );
    $this->assertFileExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.xml' );
    $this->assertFileNotExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.json' );

    // test autoloader off
    $storage       = $this->getStorage( '.test/FrameworkStoragePermanentTest/test' );
    $storage->auto = false;
    $this->assertEquals( null, $storage->get( 'test1.test2.test3' ) );

    // test loading
    $storage->auto = true;
    $this->assertEquals( 3, $storage->get( 'test1.test2.test3' ) );

    // test file destroy
    $storage->remove();
    $this->assertFileNotExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.xml' );
  }
  /**
   * @throws \Framework\Exception\System
   */
  public function testDirectory() {

    // test all native format unserializer
    $storage = $this->getStorage( '.test/FrameworkStoragePermanentTest/test/' );
    $this->assertEquals( 3, $storage->get( 'test-json:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'test-ini:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'test-xml:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'test-native:test1.test2.test3' ) );

    // write out all data again to check the serialize
    $storage->save( 'test-json', 'json' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertJsonFileEqualsJsonFile(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.json',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-json.json'
    );
    $storage->save( 'test-xml', 'xml' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertXmlFileEqualsXmlFile(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.xml',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-xml.xml'
    );
    $storage->save( 'test-ini', 'ini' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertEquals(
      parse_ini_file( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.ini' ),
      parse_ini_file( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-ini.ini' )
    );
    $storage->save( 'test-native', 'pser' );
    $this->assertEquals( null, $storage->getException() );
    $this->assertEquals(
      trim( file_get_contents( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.pser' ) ),
      trim( file_get_contents( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-native.pser' ) )
    );

    // try the namespace remove
    $storage->remove( 'test-native' );
    $this->assertEquals( null, $storage->get( 'test-native:test1.test2.test3' ) );
    $this->assertFileNotExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-native.pser' );

    // try namespace format change
    $storage->save( 'test-json', 'pser' );
    $this->assertEquals( 3, $storage->get( 'test-json:test1.test2.test3' ) );
    $this->assertFileNotExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-json.json' );
    $this->assertFileExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/test-json.pser' );
  }

  private function getStorage( $path ) {
    $storage = new \Framework\Storage\File( $path, [
      new Converter\Json(),
      new Converter\Xml(),
      new Converter\Ini(),
      new Converter\Native()
    ] );
    return $storage;
  }
}
