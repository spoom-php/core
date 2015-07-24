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
    $this->assertEquals( 'en', $extension->option( 'default:localization' ) );
    $this->assertEquals( 'Unknown exception', $extension->text( 'framework-exception:#0' ) );

    // check cloning
    $extension2 = clone $extension;
    $this->assertFalse( $extension2->configuration === $extension );
  }
  /**
   * @param \Framework\Extension $extension
   *
   * @dataProvider provider
   */
  public function testAdvance( \Framework\Extension $extension ) {

    // check file access
    $this->assertEquals( 'extension/framework/configuration/default.json', $extension->file( 'default.json', 'configuration' ) );
    $this->assertEquals( [ 'extension/framework/configuration/default.json' ], $extension->file( '|^default|', 'configuration' ) );

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
