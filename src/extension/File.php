<?php namespace Spoom\Framework;

use Spoom\Framework\Helper\StreamInterface;

/**
 * Interface FileInterface
 * @package Framework
 */
interface FileInterface {

  /**
   * Path to the file (proxy to ->getPath())
   *
   * @return string
   */
  public function __toString();

  /**
   * Check if the path is exists, with the given meta data
   *
   * @param array $meta Optional metadata checks
   *
   * @return bool
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function exist( array $meta = [] );
  /**
   * Write content to a file
   *
   * @param string|StreamInterface $content
   * @param bool                   $append Append or rewrite the file
   * @param array                  $meta   Change (or set the new) file's meta
   *
   * @throws Exception If the path is a directory not a file
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function write( $content, $append = true, array $meta = [] );
  /**
   * Read a file contents
   *
   * note: Do not throw exception if the path is not exists, just silently returns empty value
   *
   * @param StreamInterface|null $stream Output stream if not null
   *
   * @return string|null
   * @throws Exception If the path is a directory not a file
   * @throws Exception If the path is not readable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function read( $stream = null );

  /**
   * Get a sub-path
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public function get( $path );
  /**
   * List directory contents
   *
   * @param string|null $pattern   Search pattern if any
   * @param bool        $recursive List subdirectory contents
   * @param bool        $directory Include or exclude directories
   *
   * @return FileInterface[]
   * @throws Exception If the path is a file not a directory
   * @throws Exception If the path is not readable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function search( $pattern = null, $recursive = false, $directory = true );
  /**
   * Create an empty directory or file
   *
   * @param array $meta The newly created path meta
   *
   * @return FileInterface
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function create( array $meta = [] );
  /**
   * Remove a directory or a file
   *
   * This will remove the directory AND ALL the contents in it
   *
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function destroy();

  /**
   * Copy the content (or a directory with sub-content) to the destination
   *
   * Overwrites the destination silently
   *
   * @param string $destination
   * @param bool   $move Remove the source after the successful copy
   *
   * @return FileInterface
   * @throws Exception If the path is not readable or the destination is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function copy( $destination, $move = false );

  /**
   * Get the path string
   *
   * @param bool $real Get the "real" and absolute path, or the normalized path within the system
   *
   * @return string
   */
  public function getPath( $real = false );
  /**
   * Get a (or all) meta for a specific path
   *
   * @param string|null $name
   *
   * @return array|mixed
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function getMeta( $name = null );
  /**
   * Set a path metadata, if not read only
   *
   * @param mixed       $value
   * @param string|null $name
   *
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function setMeta( $value, $name = null );

  /**
   * @return File\SystemInterface
   */
  public function getSystem();
  /**
   * @return bool
   */
  public function isDirectory();
  /**
   * @return bool
   */
  public function isReadable();
  /**
   * @return bool
   */
  public function isWriteable();
}
/**
 * Class File
 * @package Framework
 */
class File implements FileInterface {

  /**
   * @var File\SystemInterface
   */
  private $_system;
  /**
   * @var string
   */
  private $_path;

  /**
   *
   * @param File\SystemInterface $system
   * @param string               $path
   */
  public function __construct( File\SystemInterface $system, $path ) {
    $this->_system = $system;
    $this->_path   = $path;
  }

  //
  public function __toString() {
    return $this->getPath( true );
  }

  //
  public function exist( array $meta = [] ) {
    return $this->_system->exist( $this->_path, $meta );
  }

  //
  public function write( $content, $append = true, array $meta = [] ) {
    $this->_system->write( $this->_path, $content, $append, $meta );
  }
  //
  public function read( $stream = null ) {
    return $this->_system->read( $this->_path, $stream );
  }

  //
  public function get( $path ) {
    return empty( $path ) ? $this : new static( $this->_system, File\System::directory( $this->getPath() ) . $path );
  }
  //
  public function search( $pattern = null, $recursive = false, $directory = true ) {
    return $this->_system->search( $this->_path, $pattern, $recursive, $directory );
  }
  //
  public function create( array $meta = [] ) {
    return $this->_system->create( $this->_path, $meta );
  }
  //
  public function destroy() {
    $this->_system->destroy( $this->_path );
  }

  //
  public function copy( $destination, $move = false ) {
    return $this->_system->copy( $this->_path, $destination, $move );
  }

  //
  public function getPath( $real = false ) {
    return $real ? $this->_system->getPath( $this->_path ) : $this->_path;
  }
  //
  public function getMeta( $name = null ) {
    return $this->_system->getMeta( $this->_path, $name );
  }
  //
  public function setMeta( $value, $name = null ) {
    return $this->_system->setMeta( $this->_path, $value, $name );
  }

  //
  public function getSystem() {
    return $this->_system;
  }
  //
  public function isDirectory() {
    return $this->getMeta( File\System::META_TYPE ) == File\System::TYPE_DIRECTORY;
  }
  //
  public function isReadable() {
    return $this->getMeta( File\System::META_PERMISSION_READ );
  }
  //
  public function isWriteable() {
    return $this->getMeta( File\System::META_PERMISSION_WRITE );
  }
}
