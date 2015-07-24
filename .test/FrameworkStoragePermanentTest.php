<?php

class FrameworkStoragePermanentTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::setup( function () {
    } );

    // create test files
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.xml',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/xml.xml'
    );
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.json',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/json.json'
    );
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.ini',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/ini.ini'
    );
    @copy(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.php',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/php.php'
    );

    parent::__construct( $name, $data, $dataName );
  }
  public function __destruct() {

    // remove all generated files
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.json' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.xml' );

    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/json.json' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/xml.xml' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/ini.ini' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/php.php' );
    @unlink( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/json.php' );
  }

  /**
   * @throws \Framework\Exception\System
   */
  public function testFile() {

    $storage = new \Framework\Storage\File( '.test/FrameworkStoragePermanentTest/test' );
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
    $storage->save( 'xml' );
    $this->assertFileExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.xml' );
    $this->assertFileNotExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test.json' );

    // test autoloader off
    $storage       = new \Framework\Storage\File( '.test/FrameworkStoragePermanentTest/test' );
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
    $storage = new \Framework\Storage\File( '.test/FrameworkStoragePermanentTest/test/' );
    $this->assertEquals( 3, $storage->get( 'json:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'ini:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'xml:test1.test2.test3' ) );
    $this->assertEquals( 3, $storage->get( 'php:test1.test2.test3' ) );

    // write out all data again to check the serialize
    $storage->save( 'json', 'json' );
    $this->assertJsonFileEqualsJsonFile(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.json',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/json.json'
    );
    $storage->save( 'xml', 'xml' );
    $this->assertXmlFileEqualsXmlFile(
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.xml',
      \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/xml.xml'
    );
    $storage->save( null, 'ini' );
    $this->assertEquals(
      parse_ini_file( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.ini' ),
      parse_ini_file( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/ini.ini' )
    );
    $storage->save( null, 'php' );
    $this->assertEquals(
      trim( file_get_contents( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/sample.php' ) ),
      trim( file_get_contents( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/php.php' ) )
    );

    // try the namespace remove
    $storage->remove( 'php' );
    $this->assertEquals( null, $storage->get( 'php:test1.test2.test3' ) );
    $this->assertFileNotExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/php.php' );

    // try namespace format change
    $storage->save( 'php', 'json' );
    $this->assertEquals( 3, $storage->get( 'json:test1.test2.test3' ) );
    $this->assertFileNotExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/json.json' );
    $this->assertFileExists( \Framework::PATH_BASE . '.test/FrameworkStoragePermanentTest/test/json.php' );
  }
}
