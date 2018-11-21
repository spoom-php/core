<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

//
class ExtensionTest extends TestCase {

  /**
   * @param ExtensionInterface $extension
   *
   * @dataProvider provider
   */
  public function testBasic( ExtensionInterface $extension ) {

    // test instancing
    $this->assertTrue( Extension::instance() === $extension );
  }
  /**
   * @param ExtensionInterface $extension
   *
   * @dataProvider provider
   */
  public function testAdvance( ExtensionInterface $extension ) {

    // check file access
    $this->assertTrue( $extension->file( 'extension/Extension.php' )->exist() );
    $this->assertEquals( 2, count( $extension->file( 'extension/Exception/', '*' ) ) );
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
