<?php namespace Framework\File;

use Framework\Exception;
use Framework\FileInterface;
use Framework\Helper\Stream;
use Framework\Helper\StreamInterface;

/**
 * Interface SystemInterface
 * @package Framework\File
 */
interface SystemInterface {

  /**
   * Missing, not a directory or not readable root path
   *
   * @param string $root The invalid path
   */
  const EXCEPTION_ROOT_INVALID = 'framework#0';
  /**
   * The given path is outside the root
   */
  const EXCEPTION_PATH_INVALID = 'framework#0';
  /**
   * The path's type is not suitable for the operation
   *
   * @param string $path
   * @param string $allow
   */
  const EXCEPTION_TYPE_INVALID = 'framework#0';
  /**
   * Don't have enough permission for the operation
   *
   * @param string $path
   */
  const EXCEPTION_PERMISSION_INVALID = 'framework#0';

  const DIRECTORY_SEPARATOR = '/';
  const DIRECTORY_CURRENT   = '.';
  const DIRECTORY_PREVIOUS  = '..';

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
   * Modification time of the path (in UNIX timestamp)
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
   * Check if the path is exists, with the given meta data
   *
   * @param string $path
   * @param array  $meta Optional metadata checks
   *
   * @return bool
   */
  public function exist( $path, array $meta = [] );
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
   * note: Don't throw exception if the path is not exist, just silently returns empty value
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
   * Only creates path if not already exists. The $meta parameter not applied for an existed path
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
   * Remove a directory or a file
   *
   * This will remove the directory AND ALL the contents in it
   *
   * @param string $path
   *
   * @throws Exception If the path is not writeable
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function destroy( $path );

  /**
   * Copy the content (or a directory with sub-content) to the destination
   *
   * Overwrites the destination silently
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
   * Read only metadata will be ignored!
   *
   * @param string      $path
   * @param mixed       $value
   * @param string|null $name
   *
   * @throws Exception Unsuccessful operation, due to the underlying system
   */
  public function setMeta( $path, $value, $name = null );
}

/**
 * Class System
 *
 * note: All links will be converted to realpath and handled by that
 *
 * @package Framework\File
 */
class System implements SystemInterface {

  const EXCEPTION_FAIL = 'framework#0';

  /**
   * @var string
   */
  private $root;

  /**
   * @param string $root The root path for the filesystem. It MUST exists!
   *
   * @throws Exception\Strict Invalid root path
   */
  public function __construct( $root ) {

    $_root = is_link( $root ) ? realpath( $root ) : $root;
    if( $_root === false || !is_dir( $_root ) || !is_readable( $_root ) ) throw new Exception\Strict( static::EXCEPTION_ROOT_INVALID, [ 'root' => $root ] );
    else {

      $this->root = rtrim( $_root, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
    }
  }

  /**
   * @inheritdoc
   */
  public function exist( $path, array $meta = [] ) {
    $path = $this->getPath( $path );

    $result = file_exists( $path );
    if( $result && !empty( $meta ) ) {
      // TODO check agains the metadata array
    }

    return $result;
  }
  /**
   * @inheritdoc
   */
  public function write( $path, $content, $append = true, array $meta = [] ) {

    $_meta = $this->getMeta( $path );
    if( $_meta[ static::META_TYPE ] != static::TYPE_FILE ) throw new Exception\Strict( static::EXCEPTION_TYPE_INVALID, [ 'path' => $path, 'allow' => static::TYPE_FILE ] );
    else if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new Exception\Strict( static::EXCEPTION_PERMISSION_INVALID, [ 'path' => $path ] );
    else {

      // create the directory
      // TODO add default meta for the silent directory creation
      $directory = dirname( $path );
      $this->create( $directory );

      // create write operation pointer
      $resource = fopen( $this->getPath( $path ), $append ? 'a' : 'w' );
      if( !$resource ) throw new Exception\System( static::EXCEPTION_FAIL, [ 'path' => $path ] );
      else {

        $resource = Stream::instance( $resource );
        $resource->write( $content );

        // apply metadata for the new/edited file
        $this->setMeta( $path, $meta );
      }
    }
  }
  /**
   * @inheritdoc
   */
  public function read( $path, $stream = null ) {
    if( !$this->exist( $path ) ) return $stream ? null : '';
    else {

      $meta = $this->getMeta( $path );
      if( $meta[ static::META_TYPE ] != static::TYPE_FILE ) throw new Exception\Strict( static::EXCEPTION_TYPE_INVALID, [ 'path' => $path, 'allow' => static::TYPE_FILE ] );
      else if( !$meta[ static::META_PERMISSION_READ ] ) throw new Exception\Strict( static::EXCEPTION_PERMISSION_INVALID, [ 'path' => $path ] );
      else {

        // create write operation pointer
        $resource = fopen( $this->getPath( $path ), 'r' );
        if( !$resource ) throw new Exception\System( static::EXCEPTION_FAIL, [ 'path' => $path ] );
        else {

          $resource = Stream::instance( $resource );
          return $resource->read( 0, null, $stream );
        }
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function search( $path, $pattern = null, $recursive = false, $directory = true ) {

    $meta = $this->getMeta( $path );
    if( $meta[ static::META_TYPE ] != static::TYPE_DIRECTORY ) throw new Exception\Strict( static::EXCEPTION_TYPE_INVALID, [ 'path' => $path, 'allow' => static::TYPE_DIRECTORY ] );
    else if( !$meta[ static::META_PERMISSION_READ ] ) throw new Exception\Strict( static::EXCEPTION_PERMISSION_INVALID, [ 'path' => $path ] );
    else if( !$this->exist( $path ) ) return [];
    else {

      $result = [];
      $path .= rtrim( $path, static::DIRECTORY_SEPARATOR ) . static::DIRECTORY_SEPARATOR;
      $_path = $this->getPath( $path );

      $list = scandir( $_path );
      foreach( $list as $tmp ) {
        if( !in_array( $tmp, [ '.', '..' ] ) && ( !$pattern || preg_match( $pattern, $path . $tmp ) ) ) {

          $is_directory = $this->getMeta( $path . $tmp, static::META_TYPE ) == static::TYPE_DIRECTORY;
          if( ( $directory || !$is_directory ) ) {
            // TODO add to the result ($result[] = new File( $this, $path . $tmp );
          }

          // search in subdirectory
          if( $recursive && $is_directory ) {
            $result = array_merge( $result, $this->search( $path . $tmp, $pattern, $recursive, $directory ) );
          }
        }
      }

      return $result;
    }
  }
  /**
   * @inheritdoc
   */
  public function create( $path, array $meta = [] ) {

    if( !$this->exist( $path ) ) {

      // check for permissions
      $_meta = $this->getMeta( $path );
      if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new Exception\Strict( static::EXCEPTION_PERMISSION_INVALID, [ 'path' => $path ] );
      else {

        $_path = $this->getPath( $path );
        if( $_meta[ static::META_TYPE ] == static::TYPE_DIRECTORY ) $result = @mkdir( $_path, $_meta[ static::META_PERMISSION ], true );
        else {

          // create the directory
          // TODO add default meta for the silent directory creation
          $directory = dirname( $path );
          $this->create( $directory );

          $result = @touch( $_path );
        }

        if( !$result ) throw new Exception\System( static::EXCEPTION_FAIL, [ 'path' => $path ] );
        else {

          // apply metadata for the new path
          $this->setMeta( $path, $meta );
        }
      }
    }

    // TODO return new File( $this, $path );
  }
  /**
   * @inheritdoc
   */
  public function destroy( $path ) {
    if( $this->exist( $path ) ) {

      // check for permissions
      $_meta = $this->getMeta( $path );
      if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new Exception\Strict( static::EXCEPTION_PERMISSION_INVALID, [ 'path' => $path ] );
      else {

        $_path = $this->getPath( $path );
        if( $_meta[ static::META_TYPE ] != static::TYPE_DIRECTORY ) $result = @unlink( $_path );
        else {

          // remove contents first
          $list = $this->search( $path );
          foreach( $list as $file ) {
            $file->destroy();
          }

          // remove the directory itself lasts
          $result = @rmdir( $_path );
        }

        if( !$result ) throw new Exception\System( static::EXCEPTION_FAIL, [ 'path' => $path ] );
      }
    }
  }

  public function copy( $path, $destination, $move = false ) {
    // TODO Implement copy()
  }

  /**
   * @inheritdoc
   *
   * TODO cache paths
   */
  public function getPath( $path = '' ) {
    if( empty( $path ) ) return $this->root;
    else {

      // normalize path
      $_path = static::path( $path );
      if( static::DIRECTORY_SEPARATOR != DIRECTORY_SEPARATOR ) {

        // replace standard directory separators to local system specific, if needed
        $_path = str_replace( static::DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $_path );
      }

      // resolve link
      $_path = $this->root . $_path;
      if( is_link( $_path ) ) {
        $_path = realpath( $_path );
      }

      return $_path;
    }
  }
  /**
   * @inheritdoc
   *
   * TODO optimize simple access (eg: only for type)
   */
  public function getMeta( $path, $name = null ) {
    $_path = $this->getPath( $path );

    // handle missing path
    if( !file_exists( $_path ) ) {

      // search for the first exists path to determine the right permissions for create
      $root = $_path;
      do {
        $root = dirname( $root );
      } while( !file_exists( $root ) );

      $info = new \SplFileInfo( $root );
      $meta = [
        static::META_TYPE => $_path[ strlen( $_path ) - 1 ] == static::DIRECTORY_SEPARATOR ? static::TYPE_DIRECTORY : static::TYPE_FILE,
        static::META_TIME => 0,

        static::META_PERMISSION       => $info->getPerms(),
        static::META_PERMISSION_READ  => $info->isReadable(),
        static::META_PERMISSION_WRITE => $info->isWritable()
      ];
    } else {

      // add basic metadata
      $info = new \SplFileInfo( $_path );
      $meta = [
        static::META_TYPE => $info->isDir() ? static::TYPE_DIRECTORY : static::TYPE_FILE,
        static::META_TIME => $info->getMTime(),

        static::META_PERMISSION       => $info->getPerms(),
        static::META_PERMISSION_READ  => $info->isReadable(),
        static::META_PERMISSION_WRITE => $info->isWritable()
      ];

      // TODO add size for a directory, only if needed
      if( $meta[ static::META_TYPE ] == static::TYPE_FILE ) {
        $meta[ static::META_SIZE ] = $info->getSize();
      }

      // search for mime type, only if needed
      if( $name == static::META_MIME ) {

        $tmp = class_exists( '\finfo' ) || function_exists( 'mime_content_type' );
        if( !$tmp ) throw new Exception\System( \Framework::EXCEPTION_FEATURE_MISSING, [ 'name' => 'fileinfo@0.1.0' ] );
        else if( !class_exists( '\finfo' ) ) $tmp = mime_content_type( $_path );
        else {

          $info = new \finfo( FILEINFO_MIME_TYPE );
          $tmp  = $info->file( $_path );
          if( in_array( $tmp, [ 'application/octet-stream', 'inode/x-empty' ] ) ) {
            $tmp = $info->buffer( $this->read( $path ) );
          }
        }

        $meta[ static::META_MIME ] = $tmp ?: null;
      }
    }

    // TODO implement support for multiple named meta
    return empty( $name ) ? $meta : ( isset( $meta[ $name ] ) ? $meta[ $name ] : null );
  }
  /**
   * @inheritdoc
   */
  public function setMeta( $path, $value, $name = null ) {
    // TODO implement permission change
  }

  /**
   * Normalize the given path
   *
   * Remove multiple (consecutive) separators and resolve static::DIRECTORY_CURRENT / static::DIRECTORY_PREVIOUS. This will remove
   * the leading separator but keep the trailing one
   *
   * @param string $path Standard path string
   *
   * @return string
   * @throws Exception\Strict The path begins with static::DIRECTORY_PREVIOUS, which will voilate the root
   */
  public static function path( $path ) {

    // deep normalization if the path contains one-dir-up segment (clean multiple separators)
    $_path = preg_replace( '#\\' . static::DIRECTORY_SEPARATOR . '+#', static::DIRECTORY_SEPARATOR, ltrim( $path, static::DIRECTORY_SEPARATOR ) );
    if( strpos( $_path, static::DIRECTORY_PREVIOUS ) !== false ) {

      $tmp      = explode( static::DIRECTORY_SEPARATOR, $_path );
      $segments = [];
      foreach( $tmp as $segment ) {
        if( $segment != static::DIRECTORY_CURRENT ) {

          $last = empty( $segments ) ? '' : $segments[ count( $segments ) - 1 ];
          if( $segment != static::DIRECTORY_PREVIOUS || in_array( $last, [ '', static::DIRECTORY_PREVIOUS ] ) ) $segments[] = $segment;
          else array_pop( $segments );
        }
      }

      $_path = implode( static::DIRECTORY_SEPARATOR, $segments );
    }

    // check for root voilation
    if( preg_match( addcslashes( '#^' . static::DIRECTORY_PREVIOUS . '(' . static::DIRECTORY_SEPARATOR . '|$)#', './' ), $_path ) ) {
      throw new Exception\Strict( static::EXCEPTION_PATH_INVALID, [ 'path' => $path ] );
    }

    return $_path;
  }
}
