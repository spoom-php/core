<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

/**
 * Class ExtensionTest
 *
 * TODO add Configuration and Localization tests
 *
 */
class ExtensionTest extends TestCase {

  /**
   * @param ExtensionInterface $extension
   *
   * @dataProvider provider
   */
  public function testBasic( ExtensionInterface $extension ) {

    // test instancing
    $this->assertTrue( Extension::instance() === $extension );

    // check cloning
    $extension2 = clone $extension;
    $this->assertFalse( $extension2->getConfiguration() === $extension );
  }
  /**
   * @param ExtensionInterface $extension
   *
   * @dataProvider provider
   */
  public function testAdvance( ExtensionInterface $extension ) {

    // check file access
    $this->assertTrue( $extension->file( 'extension/Extension.php' )->exist() );
    $this->assertEquals( 2, count( $extension->file( 'extension/Extension/', '*' ) ) );
  }
  /**
   * @return array
   */
  public function provider() {
    return [
      [ Extension::instance() ]
    ];
  }
}
