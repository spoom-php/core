<?php namespace Framework;

use Framework\Helper\StreamInterface;

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
   * Remove a directory or a file. This will remove the directory AND ALL the contents in it
   *
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function destroy();

  /**
   * Copy the content (or a directory with sub-content) to the destination. Overwrites the destination silently
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
   * @throws Exception Try to set a read-only meta
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function setMeta( $value, $name = null );
}

