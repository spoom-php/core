<?php

class FrameworkExtensionTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::setup( function () {
    } );

    parent::__construct( $name, $data, $dataName );
  }

  /**
   * @param \Framework\Extension $extension
   *
   * @dataProvider provider
   */
  public function testBasic( \Framework\Extension $extension ) {

    // test instancing
    $this->assertTrue( \Framework\Extension::instance( 'framework' ) === $extension );
    $this->assertFalse( new \Framework\Extension( 'framework' ) === $extension );

    // configuration and localization access check
    $this->assertEquals( 'en', $extension->option( 'request:localization' ) );
    $this->assertEquals( 'Unknown exception', $extension->text( 'framework-exception:#0' ) );

    // check cloning
    $extension2 = clone $extension;
    $this->assertFalse( $extension2->configuration === $extension );

    // environment specific configuration check
    $this->assertEquals( 'debug', $extension2->option( 'request:level.log' ) );
    $extension2->configuration->setEnvironment( 'production' );
    $this->assertEquals( 'notice', $extension2->option( 'request:level.log' ) );
    $extension2->configuration->setEnvironment( 'development' );
    $this->assertEquals( 'debug', $extension2->option( 'request:level.log', 2 ) );
  }
  /**
   * @param \Framework\Extension $extension
   *
   * @dataProvider provider
   */
  public function testAdvance( \Framework\Extension $extension ) {

    // check file access
    $this->assertEquals( 'extension/framework/configuration/request.json', $extension->file( 'request.json', 'configuration' ) );
    $this->assertEquals( [ 'extension/framework/configuration/request.json' ], $extension->file( '|^request|', 'configuration' ) );

    // check class searching and creating
    $this->assertEquals( '\Framework\Storage', $extension->library( [ 'test1', 'storage', 'request' ] ) );
    $this->assertEquals( 'Framework\Storage', get_class( $extension->create( [ 'test1', 'storage', 'request' ] ) ) );
  }

  // TODO test uncovered `Exception\Event` and `Exception\Helper` methods

  /**
   * @return array
   */
  public function provider() {
    return [
      [ \Framework\Extension::instance( 'framework' ) ]
    ];
  }
}
