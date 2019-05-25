<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

//
class PackageTest extends TestCase {

  /**
   * @param PackageInterface $package
   *
   * @dataProvider provider
   */
  public function testBasic( PackageInterface $package ) {

    // test instancing
    $this->assertTrue( Package::instance() === $package );
  }

  /**
   * @return array
   */
  public function provider() {
    return [
      [ Package::instance() ]
    ];
  }
}
