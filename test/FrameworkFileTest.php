<?php namespace Spoom\Framework;

use PHPUnit\Framework\TestCase;

/**
 * Class FrameworkFileTest
 *
 * TODO cleanup leftover files
 */
class FrameworkFileTest extends TestCase {

  private $root = __DIR__ . DIRECTORY_SEPARATOR . 'FrameworkFileTest' . DIRECTORY_SEPARATOR;

  /**
   * Test basix filesystem operations
   */
  public function testBasic() {

    // test path normalization
    $this->assertEquals( 'test/', File\System::path( './test/' ) );
    $this->assertEquals( 'test', File\System::path( './test' ) );
    $this->assertEquals( 'test', File\System::path( './a/../test' ) );
    $this->assertEquals( 'test/', File\System::path( 'a/../test/' ) );
    $this->assertEquals( 'test/', File\System::path( '/a/../test/' ) );
    $this->assertEquals( '', File\System::path( '/' ) );

    // test directory forcing
    $this->assertEquals( 'a/../test/', File\System::directory( 'a/../test' ) );
    $this->assertEquals( 'a/../test/', File\System::directory( '/a/../test/' ) );
    $this->assertEquals( '', File\System::directory( '/' ) );
  }

  /**
   * Test local filesystem basic access
   *
   * @depends testBasic
   */
  public function testSystem() {

    // test local system creation
    $system = new File\System( $this->root );
    $this->assertEquals( $this->root, $system->getPath( '' ) );

    // test basic meta getters
    $meta = $system->getMeta( '' );
    $this->assertNotEmpty( $meta );
    $this->assertEquals( File\System::TYPE_DIRECTORY, $meta[ File\System::META_TYPE ] );
    $this->assertEquals( File\System::TYPE_DIRECTORY, $system->getMeta( '', File\System::META_TYPE ) );
    $this->assertEquals( [
      File\System::META_TYPE => File\System::TYPE_DIRECTORY
    ], $system->getMeta( '', [ File\System::META_TYPE ] ) );

    // test non-exist path meta
    $this->assertEquals( File\System::TYPE_DIRECTORY, $system->getMeta( '_a/', File\System::META_TYPE ) );
    $this->assertEquals( File\System::TYPE_FILE, $system->getMeta( '_a', File\System::META_TYPE ) );

    // test file/directory existance checking
    $this->assertTrue( $system->exist( 'a.txt' ) );
    $this->assertTrue( $system->exist( 'a/b.txt' ) );
    $this->assertFalse( $system->exist( '_a' ) );

    // test directory listing
    $this->assertEquals( 4, count( $system->search( '' ) ) );
    $this->assertEquals( 8, count( $system->search( '', null, true ) ) );
    $this->assertEquals( 2, count( $system->search( '', '/^[ab]/', false, false ) ) );
    $this->assertEquals( 3, count( $system->search( '', '/^[ab]/' ) ) );
  }
  /**
   * Test local filesystem IO operations (read,write,delete)
   *
   * @depends testSystem
   */
  public function testSystemWrite() {
    $system = new File\System( $this->root );

    // check basic file reads (simple and from subdirectory)
    $this->assertEquals( 'a.txt', rtrim( $system->read( 'a.txt' ), "\r\n" ) );
    $this->assertEquals( 'a/b.txt', rtrim( $system->read( 'a/b.txt' ), "\r\n" ) );

    // test empty file creation
    $this->assertFalse( $system->exist( 'd.txt' ) );
    $system->create( 'd.txt' );
    $this->assertTrue( $system->exist( 'd.txt' ) );

    // test empty file creation in non-existed subdirectory...which will test the directory creation eventually
    $this->assertFalse( $system->exist( 'b/' ) );
    $system->create( 'b/c.txt' );
    $this->assertTrue( $system->getMeta( 'b/', File\System::META_TYPE ) == File\System::TYPE_DIRECTORY );
    $this->assertTrue( $system->exist( 'b/c.txt' ) );

    // test simple file deletion and directory (with file in it) deletion (this will remove the files created above)
    $system->destroy( 'd.txt' );
    $this->assertFalse( $system->exist( 'd.txt' ) );
    $system->destroy( 'b' );
    $this->assertFalse( $system->exist( 'b/' ) );
  }
  /**
   * Test copy and move feature
   *
   * @depends testSystem
   */
  public function testSystemCopy() {
    $system = new File\System( $this->root );

    // test simple file copy
    $system->copy( 'c.txt', 'd.txt' );
    $this->assertTrue( $system->exist( 'c.txt' ) );
    $this->assertTrue( $system->exist( 'd.txt' ) );
    $this->assertFileEquals( (string) new File( $system, 'c.txt' ), (string) new File( $system, 'd.txt' ) );

    // test deep directory copy
    $system->copy( 'a/', 'b/' );
    $this->assertTrue( $system->exist( 'a/' ) );
    $this->assertTrue( $system->exist( 'b/' ) );
    $this->assertTrue( $system->exist( 'b/c.txt' ) );
    $this->assertFileEquals( (string) new File( $system, 'a/b/c.txt' ), (string) new File( $system, 'b/b/c.txt' ) );

    // test simple file move
    $system->copy( 'd.txt', 'f.txt', true );
    $this->assertFalse( $system->exist( 'd.txt' ) );
    $this->assertTrue( $system->exist( 'f.txt' ) );

    // test deep directory move
    $system->copy( 'b/', 'c/', true );
    $this->assertFalse( $system->exist( 'b/' ) );
    $this->assertTrue( $system->exist( 'c/' ) );
    $this->assertTrue( $system->exist( 'c/c.txt' ) );

    $system->destroy( 'f.txt' );
    $system->destroy( 'c/' );
  }
}