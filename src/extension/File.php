<?php namespace Spoom\Framework;

use Spoom\Framework\Helper\StreamInterface;

/**
 * Interface FileInterface
 * @package Framework
 *
 * @property-read string               $path
 * @property array                     $meta
 * @property-read File\SystemInterface $system
 * @property-read bool                 $directory
 * @property-read bool                 $readable
 * @property-read bool                 $writeable
 */
interface FileInterface {

  /**
   * Path to the file (proxy to ->getPath())
   *
   * @return string
   */
  public function __toString(): string;

  /**
   * Check if the path is exists, with the given meta data
   *
   * @param array $meta Optional metadata checks
   *
   * @return bool
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function exist( array $meta = [] ): bool;
  /**
   * Write content to a file
   *
   * @param string|StreamInterface $content
   * @param bool                   $append Append or rewrite the file
   * @param array                  $meta   Change (or set the new) file's meta
   *
   * @throws File\SystemExceptionTypeInvalid If the path is a directory not a file
   * @throws File\SystemExceptionPermission If the path is not writeable
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function write( $content, bool $append = true, array $meta = [] );
  /**
   * Read a file contents
   *
   * note: Do not throw exception if the path is not exists, just silently returns empty value
   *
   * @param StreamInterface|null $stream Output stream if not null
   *
   * @return string|null
   * @throws File\SystemExceptionTypeInvalid If the path is a directory not a file
   * @throws File\SystemExceptionPermission If the path is not readable
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function read( $stream = null ): ?string;

  /**
   * Get a sub-path
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public function get( string $path ): FileInterface;
  /**
   * List directory contents
   *
   * @param string|null $pattern   Search pattern if any
   * @param bool        $recursive List subdirectory contents
   * @param bool        $directory Include or exclude directories
   *
   * @return FileInterface[]
   * @throws File\SystemExceptionTypeInvalid If the path is a file not a directory
   * @throws File\SystemExceptionPermission If the path is not readable
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function search( ?string $pattern = null, bool $recursive = false, bool $directory = true ): array;
  /**
   * Create an empty directory or file
   *
   * @param array $meta The newly created path meta
   *
   * @return FileInterface
   * @throws File\SystemExceptionPermission If the path is not writeable
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function create( array $meta = [] ): FileInterface;
  /**
   * Remove a directory or a file
   *
   * This will remove the directory AND ALL the contents in it
   *
   * @throws File\SystemExceptionPermission If the path is not writeable
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
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
   * @throws File\SystemExceptionPermission If the path is not readable or the destination is not writeable
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function copy( string $destination, bool $move = false ): FileInterface;

  /**
   * Get the path string
   *
   * @param bool $real Get the "real" and absolute path, or the normalized path within the system
   *
   * @return string
   */
  public function getPath( bool $real = false ): string;
  /**
   * Get a (or all) meta for a specific path
   *
   * @param string[]|string|null $names
   *
   * @return array|mixed
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function getMeta( $names = null );
  /**
   * Set a path metadata, if not read only
   *
   * @param mixed       $value
   * @param string|null $name
   *
   * @throws File\SystemException Unsuccessful operation, due to the underlying system
   */
  public function setMeta( $value, ?string $name = null );

  /**
   * @return File\SystemInterface
   */
  public function getSystem(): File\SystemInterface;
  /**
   * @return bool
   */
  public function isDirectory(): bool;
  /**
   * @return bool
   */
  public function isReadable(): bool;
  /**
   * @return bool
   */
  public function isWriteable(): bool;
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
  public function __construct( File\SystemInterface $system, string $path ) {
    $this->_system = $system;
    $this->_path   = $path;
  }

  //
  public function __toString(): string {
    return $this->getPath( true );
  }

  //
  public function exist( array $meta = [] ): bool {
    return $this->_system->exist( $this->_path, $meta );
  }

  //
  public function write( $content, bool $append = true, array $meta = [] ) {
    $this->_system->write( $this->_path, $content, $append, $meta );
  }
  //
  public function read( $stream = null ): string {
    return $this->_system->read( $this->_path, $stream );
  }

  //
  public function get( string $path ): FileInterface {
    return empty( $path ) ? $this : new static( $this->_system, File\System::directory( $this->getPath() ) . $path );
  }
  //
  public function search( ?string $pattern = null, bool $recursive = false, bool $directory = true ): array {
    return $this->_system->search( $this->_path, $pattern, $recursive, $directory );
  }
  //
  public function create( array $meta = [] ): FileInterface {
    return $this->_system->create( $this->_path, $meta );
  }
  //
  public function destroy() {
    $this->_system->destroy( $this->_path );
  }

  //
  public function copy( string $destination, bool $move = false ): FileInterface {
    return $this->_system->copy( $this->_path, $destination, $move );
  }

  //
  public function getPath( bool $real = false ): string {
    return $real ? $this->_system->getPath( $this->_path ) : $this->_path;
  }
  //
  public function getMeta( $names = null ) {
    return $this->_system->getMeta( $this->_path, $names );
  }
  //
  public function setMeta( $value, ?string $name = null ) {
    return $this->_system->setMeta( $this->_path, $value, $name );
  }

  //
  public function getSystem(): File\SystemInterface {
    return $this->_system;
  }
  //
  public function isDirectory(): bool {
    return $this->getMeta( File\System::META_TYPE ) == File\System::TYPE_DIRECTORY;
  }
  //
  public function isReadable(): bool {
    return $this->getMeta( File\System::META_PERMISSION_READ );
  }
  //
  public function isWriteable(): bool {
    return $this->getMeta( File\System::META_PERMISSION_WRITE );
  }
}
