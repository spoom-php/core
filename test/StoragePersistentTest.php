<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

class StoragePersistentTest extends TestCase {

  const TEST_DIRECTORY = __DIR__ . '/StoragePersistentTest/';

  public function testBasic() {
    $storage = $this->getStorage();

    // test empty access
    $this->assertNull( $storage[ 'namespace01:missing-key' ], 'There shouldn\'t be any data in the storage, even with autoload' );
    $this->assertEmpty( $storage->getSource(), 'Missing key read shouldn\'t modify anything in the storage' );

    // test data write
    $storage[ 'namespace01:key01' ] = 1;
    $this->assertEquals( 1, $storage[ 'namespace01:key01' ] );
    $storage->save( [ 'namespace01' ] );
    $this->assertEquals( 1, $storage[ 'namespace01:key01' ] );
    $this->assertFileExists( self::TEST_DIRECTORY . 'test/namespace01.json', 'There should be a saved namespace file' );

    // test data edit
    $storage[ 'namespace01:key02' ] = 2;
    unset( $storage[ 'namespace01:key01' ] );
    $storage->save( [ 'namespace01' ] );
    $this->assertJsonStringEqualsJsonString( '{"key02":2}', file_get_contents( self::TEST_DIRECTORY . 'test/namespace01.json' ), "There should be only 'key02' in the storage file" );

    // test data remove
    $storage->remove( [ 'namespace01' ] );
    $this->assertFileNotExists( self::TEST_DIRECTORY . 'test/namespace01.json', 'There shouldn\'t be a saved namespace file' );

    // test pre-existing namespace read
    $this->assertEquals( 'namespace02key01', $storage[ 'namespace02:key01' ] );
  }

  /**
   * @depends testBasic
   */
  public function testAdvanced() {
    $storage = $this->getStorage();

    // test manual namespace
    $this->assertEquals( null, $storage[ 'namespace-manual:key01' ], "There should be no data in 'namespace-manual' before the load" );
    $storage->load( [ 'namespace-manual' ] );
    $this->assertEquals( 'namespace-manualkey01', $storage[ 'namespace-manual:key01' ], "There should be data in 'namespace-manual' after the load" );

    //
    $this->assertEquals( 'namespace-readonlykey01', $storage[ 'namespace-readonly:key01' ], "There should be data in 'namespace-readonly'" );
    $this->expectException( \LogicException::class );
    $storage[ 'namespace-readonly:key01' ] = 'edited';
  }

  /**
   * @depends testBasic
   */
  public function testMissing() {
    $storage = $this->getStorage();

    $this->expectException( \LogicException::class );
    $storage[ 'namespace03:key01' ] = 1;
  }

  /**
   * @depends testBasic
   */
  public function testEnvironment() {
    $storage = $this->getStorage();

    // test environment change
    $storage->setEnvironment( 'environment01' );
    $this->assertEquals( 'environment01', $storage[ 'namespace01:key01' ], "The values must be 'environment01' in environment01 :)" );
    $storage->setEnvironment( 'environment02' );
    $this->assertEquals( 'environment02', $storage[ 'namespace01:key01' ], "The values must be 'environment02' in environment02 :)" );
  }

  /**
   * @return Storage\File
   *
   * @throws FileRootInvalidException
   * @throws \LogicException
   */
  private function getStorage() {
    $json    = new Converter\Json();
    $storage = new Storage\File( new File( self::TEST_DIRECTORY . 'test/' ), [
      'namespace01'        => [
        'converter' => $json,
        'format'    => 'json'
      ],
      'namespace02'        => [
        'converter' => $json,
        'format'    => 'json'
      ],
      'namespace-manual'   => [
        'autoload'  => false,
        'converter' => $json,
        'format'    => 'json'
      ],
      'namespace-readonly' => [
        'readonly'  => true,
        'converter' => $json,
        'format'    => 'json'
      ]
    ] );

    return $storage;
  }
}
