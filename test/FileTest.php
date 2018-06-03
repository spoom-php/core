<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

/**
 * Class FileTest
 *
 * TODO cleanup leftover files
 */
class FileTest extends TestCase {

  private $root = __DIR__ . DIRECTORY_SEPARATOR . 'FileTest' . DIRECTORY_SEPARATOR;

  /**
   * Test basix filesystem operations
   */
  public function testBasic() {

    // test path normalization
    $this->assertEquals( 'test/', File::path( './test/' ) );
    $this->assertEquals( 'test', File::path( './test' ) );
    $this->assertEquals( 'test', File::path( './a/../test' ) );
    $this->assertEquals( 'test/', File::path( 'a/../test/' ) );
    $this->assertEquals( 'test/', File::path( '/a/../test/' ) );
    $this->assertEquals( '', File::path( '/' ) );

    // test directory forcing
    $this->assertEquals( 'a/../test/', File::directory( 'a/../test' ) );
    $this->assertEquals( 'a/../test/', File::directory( '/a/../test/' ) );
    $this->assertEquals( '', File::directory( '/' ) );
  }

  /**
   * Test local filesystem basic access
   *
   * @depends testBasic
   */
  public function testSystem() {

    // test local system creation
    $system = new File( $this->root );
    $this->assertEquals( $this->root, $system->getPath( true ) );

    // test basic meta getters
    $meta = $system->getMeta( '' );
    $this->assertNotEmpty( $meta );
    $this->assertEquals( FileInterface::TYPE_DIRECTORY, $meta[ FileInterface::META_TYPE ] );
    $this->assertEquals( FileInterface::TYPE_DIRECTORY, $system->getMeta( FileInterface::META_TYPE ) );
    $this->assertEquals( [
      FileInterface::META_TYPE => FileInterface::TYPE_DIRECTORY
    ], $system->getMeta( [ FileInterface::META_TYPE ] ) );

    // test non-exist path meta
    $this->assertEquals( FileInterface::TYPE_DIRECTORY, $system->get( '_a/' )->getMeta( FileInterface::META_TYPE ) );
    $this->assertEquals( FileInterface::TYPE_FILE, $system->get( '_a' )->getMeta( FileInterface::META_TYPE ) );

    // test file/directory existance checking
    $this->assertTrue( $system->get( 'a.txt' )->exist() );
    $this->assertTrue( $system->get( 'a/b.txt' )->exist() );
    $this->assertFalse( $system->get( '_a' )->exist() );

    // test directory listing
    $this->assertEquals( 4, count( $system->search() ) );
    $this->assertEquals( 8, count( $system->search( null, true ) ) );
    $this->assertEquals( 2, count( $system->search( '/^[ab]/', false, false ) ) );
    $this->assertEquals( 3, count( $system->search( '/^[ab]/' ) ) );
  }
  /**
   * Test local filesystem structure operations (read,write,delete)
   *
   * @depends testSystem
   */
  public function testSystemWrite() {
    $system = new File( $this->root );

    $a  = $system->get( 'a.txt' );
    $b  = $system->get( 'b/' );
    $d  = $system->get( 'd.txt' );
    $ab = $system->get( 'a/b.txt' );
    $bc = $system->get( 'b/c.txt' );

    // check basic file reads (simple and from subdirectory)
    $this->assertEquals( 'a.txt', rtrim( $a->stream()->read(), "\r\n" ) );
    $this->assertEquals( 'a/b.txt', rtrim( $ab->stream()->read(), "\r\n" ) );

    // test empty file creation
    $this->assertFalse( $d->exist() );
    $d->create();
    $this->assertTrue( $d->exist() );

    // test empty file creation in non-existed subdirectory...which will test the directory creation eventually
    $this->assertFalse( $b->exist() );
    $bc->create();
    $this->assertTrue( $b->getMeta( FileInterface::META_TYPE ) == FileInterface::TYPE_DIRECTORY );
    $this->assertTrue( $bc->exist() );

    // test simple file deletion and directory (with file in it) deletion (this will remove the files created above)
    $d->remove();
    $this->assertFalse( $d->exist() );
    $b->remove();
    $this->assertFalse( $b->exist() );
  }
  /**
   * Test copy and move feature
   *
   * @depends testSystem
   */
  public function testSystemCopy() {
    $system = new File( $this->root );

    $a = $system->get( 'a/' );
    $c = $system->get( 'c.txt' );

    // test simple file copy
    $d = $c->copy( 'd.txt' );
    $this->assertTrue( $d->exist() );
    $this->assertTrue( $c->exist() );
    $this->assertFileEquals( (string) $c, (string) $d );

    // test deep directory copy
    $b = $a->copy( 'b/' );
    $this->assertTrue( $b->exist() );
    $this->assertTrue( $a->exist() );
    $this->assertTrue( $a->get( 'c.txt' )->exist() );
    $this->assertFileEquals( (string) $a->get( 'b/c.txt' ), (string) $b->get( 'b/c.txt' ) );

    // test simple file move
    $f = $d->copy( 'f.txt', true );
    $this->assertFalse( $d->exist() );
    $this->assertTrue( $f->exist() );

    // test deep directory move
    $c = $b->copy( 'c/', true );
    $this->assertFalse( $b->exist() );
    $this->assertTrue( $c->exist() );
    $this->assertTrue( $c->get( 'c.txt' )->exist() );

    $f->remove();
    $c->remove();
  }
}
