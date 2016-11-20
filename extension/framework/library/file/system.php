<?php namespace Framework\File;

use Framework\FileInterface;
use Framework\Helper\StreamInterface;

/**
 * Interface SystemInterface
 * @package Framework\File
 */
interface SystemInterface {

  const TYPE_FILE      = 'file';
  const TYPE_DIRECTORY = 'directory';

  /**
   * Type of the path (file or directory)
   */
  const META_TYPE = 'type';
  /**
   * Path size in bytes (file size, or directory content size)
   */
  const META_SIZE = 'size';
  /**
   * MIME type of the path
   */
  const META_MIME = 'mime';
  /**
   * Creation time of the path
   */
  const META_TIME = 'time';

  /**
   * Path permission value, depends on the implementation
   */
  const META_PERMISSION = 'permission';
  /**
   * Path is readable or not
   */
  const META_PERMISSION_READ = 'permission_read';
  /**
   * Path is writeable or not
   */
  const META_PERMISSION_WRITE = 'permission_write';

  /**
   * Check if the path is exists
   *
   * @param string $path
   *
   * @return bool
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function exist( $path );
  /**
   * Write content to a file
   *
   * @param string                 $path
   * @param string|StreamInterface $content
   * @param bool                   $append Append or rewrite the file
   * @param array                  $meta   Change (or set the new) file's meta
   *
   * @throws Exception If the path is a directory not a file
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function write( $path, $content, $append = true, array $meta = [] );
  /**
   * Read a file contents
   *
   * note: Do not throw exception if the path is not exists, just silently returns empty value
   *
   * @param string               $path   The path to read, must be a file
   * @param StreamInterface|null $stream Output stream if not null
   *
   * @return string|null
   * @throws Exception If the path is a directory not a file
   * @throws Exception If the path is not readable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function read( $path, $stream = null );

  /**
   * List directory contents
   *
   * @param string      $path      The path to the directory
   * @param string|null $pattern   Search pattern if any
   * @param bool        $recursive List subdirectory contents
   * @param bool        $directory Include or exclude directories
   *
   * @return FileInterface[]
   * @throws Exception If the path is a file not a directory
   * @throws Exception If the path is not readable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function search( $path, $pattern = null, $recursive = false, $directory = true );
  /**
   * Create an empty directory or file
   *
   * @param string $path
   * @param array  $meta The newly created path meta
   *
   * @return FileInterface
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function create( $path, array $meta = [] );
  /**
   * Remove a directory or a file. This will remove the directory AND ALL the contents in it
   *
   * @param string $path
   *
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function destroy( $path );

  /**
   * Copy the content (or a directory with sub-content) to the destination. Overwrites the destination silently
   *
   * @param string $path Path to copy
   * @param string $destination
   * @param bool   $move Remove the source after the successful copy
   *
   * @return FileInterface
   * @throws Exception If the path is not readable or the destination is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function copy( $path, $destination, $move = false );

  /**
   * Get the absolute and "real" path string from the normalized version
   *
   * @param string $path The standard path, or empty for system root
   *
   * @return string
   */
  public function getPath( $path = '' );
  /**
   * Get a (or all) meta for a specific path
   *
   * @param string      $path
   * @param string|null $name
   *
   * @return array|mixed
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function getMeta( $path, $name = null );
  /**
   * Set a path metadata, if not read only
   *
   * @param string      $path
   * @param mixed       $value
   * @param string|null $name
   *
   * @throws Exception Try to set a read-only meta
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function setMeta( $path, $value, $name = null );
}
