<?php namespace Spoom\Framework\File;

use Spoom\Framework;
use Spoom\Framework\Exception;
use Spoom\Framework\File;
use Spoom\Framework\FileInterface;
use Spoom\Framework\Helper\Stream;
use Spoom\Framework\Helper\StreamInterface;
use Spoom\Framework\Helper\Text;

/**
 * Interface SystemInterface
 * @package Framework\File
 */
interface SystemInterface {

  /**
   * Clear the internal path or meta caches
   *
   * @return $this
   */
  public function reset();

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
   * @throws SystemExceptionTypeInvalid If the path is a directory not a file
   * @throws SystemExceptionPermission If the path is not writeable
   * @throws SystemException Unsuccessful operation, due to the underlying system
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
   * @throws SystemExceptionTypeInvalid If the path is a directory not a file
   * @throws SystemExceptionPermission If the path is not readable
   * @throws SystemException Unsuccessful operation, due to the underlying system
   */
  public function read( $path, $stream = null );

  /**
   * Return a path handler object
   *
   * @param string $path
   *
   * @return FileInterface
   */
  public function get( $path );
  /**
   * List directory contents
   *
   * @param string      $path      The path to the directory
   * @param string|null $pattern   Search pattern if any
   * @param bool        $recursive List subdirectory contents
   * @param bool        $directory Include or exclude directories
   *
   * @return FileInterface[]
   * @throws SystemExceptionTypeInvalid If the path is a file not a directory
   * @throws SystemExceptionPermission If the path is not readable
   * @throws SystemException Unsuccessful operation, due to the underlying system
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
   * @throws SystemExceptionPermission If the path is not writeable
   * @throws SystemException Unsuccessful operation, due to the underlying system
   */
  public function create( $path, array $meta = [] );
  /**
   * Remove a directory or a file
   *
   * This will remove the directory AND ALL the contents in it
   *
   * @param string $path
   *
   * @throws SystemExceptionPermission If the path is not writeable
   * @throws SystemException Unsuccessful operation, due to the underlying system
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
   * @return FileInterface The destination path, even if the copy wasn't successful
   * @throws SystemExceptionPermission If the path is not readable or the destination is not writeable
   * @throws SystemException Unsuccessful operation, due to the underlying system
   */
  public function copy( $path, $destination, $move = false );

  /**
   * Get the absolute and "real" path string from the normalized version
   *
   * @param string $path The standard path, or empty for system root
   *
   * @return string
   */
  public function getPath( $path );
  /**
   * Get meta for a specific path
   *
   * @param string               $path
   * @param string[]|string|null $names
   *
   * @return array|mixed
   * @throws SystemException Unsuccessful operation, due to the underlying system
   */
  public function getMeta( $path, $names = null );
  /**
   * Set a path metadata, if not read only
   *
   * Read only metadata will be ignored!
   *
   * @param string      $path
   * @param mixed       $value
   * @param string|null $name
   *
   * @throws SystemException Unsuccessful operation, due to the underlying system
   */
  public function setMeta( $path, $value, $name = null );
}

/**
 * Class Runtime
 *
 * note: All links will be converted to realpath
 *
 * @package Framework\File
 */
class System implements SystemInterface {

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
   * Cache path normalization
   *
   * @var string[]
   */
  private static $cache = [];

  /**
   * Cached real paths (key is the standard path)
   *
   * @var array
   */
  private $cache_path = [];
  /**
   * Cache paths meta values (key is the standard path, value is the cached meta values)
   *
   * @var array[]
   */
  private $cache_meta = [];

  /**
   * @var string
   */
  private $root;

  /**
   *
   * @param string $root The root path for the filesystem. It MUST exists!
   *
   * @throws SystemExceptionRootInvalid Invalid root path
   */
  public function __construct( $root ) {

    $_root = is_link( $root ) ? realpath( $root ) : $root;
    if( $_root === false || !is_dir( $_root ) || !is_readable( $_root ) ) throw new SystemExceptionRootInvalid( $root );
    else {

      $this->root = rtrim( $_root, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
    }
  }

  //
  public function reset() {

    clearstatcache();
    $this->cache_path = $this->cache_meta = [];

    return $this;
  }

  //
  public function exist( $path, array $meta = [] ) {
    $_path = $this->getPath( $path );

    $result = file_exists( $_path );
    if( $result && !empty( $meta ) ) {
      // TODO check agains the metadata array
    }

    return $result;
  }
  //
  public function write( $path, $content, $append = true, array $meta = [] ) {

    $_meta = $this->getMeta( $path );
    if( $_meta[ static::META_TYPE ] != static::TYPE_FILE ) throw new SystemExceptionTypeInvalid( $path, [ static::TYPE_FILE ] );
    else if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new SystemExceptionPermission( $path, static::META_PERMISSION_WRITE );
    else {

      // create the directory
      // TODO add default meta for the silent directory creation
      $this->create( static::directory( dirname( $path ) ) );

      // create write operation pointer
      $resource = @fopen( $this->getPath( $path ), $append ? 'a' : 'w' );
      if( !$resource ) throw new SystemException( $path, error_get_last() );
      else {

        $resource = Stream::instance( $resource );
        $resource->write( $content );

        // apply metadata for the new/edited file
        $this->setMeta( $path, $meta );
      }
    }
  }
  //
  public function read( $path, $stream = null ) {
    if( !$this->exist( $path ) ) return $stream ? null : '';
    else {

      $meta = $this->getMeta( $path );
      if( $meta[ static::META_TYPE ] != static::TYPE_FILE ) throw new SystemExceptionTypeInvalid( $path, [ static::TYPE_FILE ] );
      else if( !$meta[ static::META_PERMISSION_READ ] ) throw new SystemExceptionPermission( $path, static::META_PERMISSION_READ );
      else {

        // create write operation pointer
        $resource = @fopen( $this->getPath( $path ), 'r' );
        if( !$resource ) throw new SystemException( $path, error_get_last() );
        else {

          $resource = Stream::instance( $resource );
          return $resource->read( 0, null, $stream );
        }
      }
    }
  }

  //
  public function get( $path ) {
    return new File( $this, $path );
  }
  //
  public function search( $path, $pattern = null, $recursive = false, $directory = true ) {

    $meta = $this->getMeta( $path );
    if( $meta[ static::META_TYPE ] != static::TYPE_DIRECTORY ) throw new SystemExceptionTypeInvalid( $path, [ static::TYPE_DIRECTORY ] );
    else if( !$meta[ static::META_PERMISSION_READ ] ) throw new SystemExceptionPermission( $path, static::META_PERMISSION_READ );
    else if( !$this->exist( $path ) ) return [];
    else {

      $result = [];
      $path = static::directory( $path );
      $_path = $this->getPath( $path );

      $list = scandir( $_path );
      foreach( $list as $tmp ) {
        if( !in_array( $tmp, [ '.', '..' ] ) && ( !$pattern || preg_match( $pattern, $tmp ) ) ) {

          $is_directory = $this->getMeta( $path . $tmp, static::META_TYPE ) == static::TYPE_DIRECTORY;
          if( $is_directory ) $tmp = static::directory( $tmp );

          if( ( $directory || !$is_directory ) ) {
            $result[] = new File( $this, $path . $tmp );
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
  //
  public function create( $path, array $meta = [] ) {

    if( !$this->exist( $path ) ) {

      // check for permissions
      $_meta = $this->getMeta( $path );
      if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new SystemExceptionPermission( $path, static::META_PERMISSION_WRITE );
      else {

        $_path = $this->getPath( $path );
        if( $_meta[ static::META_TYPE ] == static::TYPE_DIRECTORY ) $result = @mkdir( $_path, $_meta[ static::META_PERMISSION ], true );
        else {

          // create the directory
          // TODO add default meta for the silent directory creation
          $this->create( static::directory( dirname( $path ) ) );

          $result = @touch( $_path );
        }

        if( !$result ) throw new SystemException( $path, error_get_last() );
        else {

          // apply metadata for the new path
          $this->setMeta( $path, $meta );
        }
      }
    }

    return new File( $this, $path );
  }
  //
  public function destroy( $path ) {
    if( $this->exist( $path ) ) {

      // check for permissions
      $_meta = $this->getMeta( $path );
      if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new SystemExceptionPermission( $path, static::META_PERMISSION_WRITE );
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

        if( !$result ) {
          throw new SystemException( $path, error_get_last() );
        }
      }
    }
  }

  //
  public function copy( $path, $destination, $move = false ) {
    if( $this->exist( $path ) ) {

      // check for permissions
      $_meta             = $this->getMeta( $path );
      $_meta_destination = $this->getMeta( $destination );
      if( !$_meta[ static::META_PERMISSION_READ ] ) throw new SystemExceptionPermission( $path, static::META_PERMISSION_READ );
      else if( !$_meta_destination[ static::META_PERMISSION_WRITE ] ) throw new SystemExceptionPermission( $destination, static::META_PERMISSION_WRITE );
      else if( $_meta_destination[ static::META_TYPE ] != $_meta[ static::META_TYPE ] ) {
        throw new SystemExceptionTypeInvalid( $destination, [ $_meta[ static::META_TYPE ] ] );
      } else {

        $_path        = $this->getPath( $path );
        $_destination = $this->getPath( $destination );
        if( $move ) $result = @rename( $_path, $_destination );
        else {

          // handle simple file copy
          if( $_meta[ static::META_TYPE ] != static::TYPE_DIRECTORY ) $result = @copy( $_path, $_destination );
          else {

            // copy base directory
            $result = $this->create( $destination );
            if( $result ) {

              // copy all contents from the directory
              $list = $this->search( $path );
              foreach( $list as $file ) {
                $file->copy( static::directory( $destination ) . substr( $file->getPath(), strlen( $path ) ) );
              }
            }
          }
        }

        if( !$result ) throw new SystemException( $path, error_get_last() );
        else {

          $this->setMeta( $destination, $_meta );
        }
      }
    }

    return new File( $this, $destination );
  }

  //
  public function getPath( $path ) {

    if( empty( $path ) || $path == static::DIRECTORY_SEPARATOR ) return $this->root;
    else if( !isset( $this->cache_path[ $path ] ) ) {

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

      $this->cache_path[ $path ] = $_path;
    }

    return $this->cache_path[ $path ];
  }
  //
  public function getMeta( $path, $names = null ) {

    // provide default values for meta types
    if( empty( $names ) ) $names = [
      static::META_TYPE,
      static::META_TIME,
      static::META_PERMISSION,
      static::META_PERMISSION_READ,
      static::META_PERMISSION_WRITE
    ];

    // save return meta, and force array names
    $result_name = !is_array( $names ) ? $names : null;
    $names       = is_array( $names ) ? $names : [ $names ];

    $result = [];
    $_path  = $file = $exist = null;
    foreach( $names as $name ) {

      // check for cached meta
      if( !isset( $this->cache_meta[ $path ][ $name ] ) ) {

        // determine path basic data for meta checks (only once)
        if( empty( $_path ) ) {

          $_path = $file = $this->getPath( $path );
          $exist = file_exists( $_path );

          // find the first exists directory for non-exists files (to provide permission values for create)
          if( !$exist ) do {
            $file = dirname( $file );
          } while( !file_exists( $file ) );
        }

        $meta = null;
        switch( $name ) {
          case static::META_TYPE:

            if( $exist ) $meta = is_dir( $_path ) ? static::TYPE_DIRECTORY : static::TYPE_FILE;
            else $meta = empty( $path ) || $path[ strlen( $path ) - 1 ] == static::DIRECTORY_SEPARATOR ? static::TYPE_DIRECTORY : static::TYPE_FILE;

            break;

          case static::META_SIZE:

            if( !$exist ) $meta = 0;
            else if( $this->getMeta( $path, static::META_TYPE ) == static::TYPE_FILE ) $meta = filesize( $_path );
            else {

              // calculate the size of a directory
              $meta = 0;
              $tmp  = $this->search( $path );
              foreach( $tmp as $t ) {
                $meta += $t->getMeta( static::META_SIZE );
              }
            }

            break;

          case static::META_MIME:

            if( !$exist ) $meta = null;
            else {

              $tmp = class_exists( '\finfo' ) || function_exists( 'mime_content_type' );
              if( !$tmp ) throw new Framework\ApplicationExceptionFeature( 'fileinfo', '0.1.0' );
              else if( !class_exists( '\finfo' ) ) $meta = mime_content_type( $_path );
              else {

                $info = new \finfo( FILEINFO_MIME_TYPE );
                $meta = $info->file( $_path );
                if( in_array( $meta, [ 'application/octet-stream', 'inode/x-empty' ] ) ) {
                  $meta = $info->buffer( $this->read( $path ) );
                }
              }
            }

            break;

          case static::META_TIME:

            $meta = $exist ? filemtime( $_path ) : 0;
            break;

          case static::META_PERMISSION:

            $meta = fileperms( $file ) & 0777;
            break;
          case static::META_PERMISSION_READ:

            $meta = is_readable( $file );
            break;

          case static::META_PERMISSION_WRITE:

            $meta = is_writeable( $file );
            break;
        }

        // create meta storage if needed
        $this->cache_meta[ $path ]          = empty( $this->cache_meta[ $path ] ) ? [] : $this->cache_meta[ $path ];
        $this->cache_meta[ $path ][ $name ] = $meta;
      }

      // extend the result with the meta
      $result[ $name ] = $this->cache_meta[ $path ][ $name ];
    }

    return $result_name ? ( isset( $result[ $result_name ] ) ? $result[ $result_name ] : null ) : $result;
  }
  //
  public function setMeta( $path, $value, $name = null ) {
    $_path = $this->getPath( $path );

    // convert direct meta set
    if( !empty( $name ) ) {
      $value = [ $name => $value ];
    }

    // change the permission
    if( isset( $value[ static::META_PERMISSION ] ) ) {
      if( @!chmod( $_path, $value[ static::META_PERMISSION ] ) ) {
        // FIXME this should throw or at least log an error
      }
    }
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
   * @throws SystemExceptionPathInvalid The path begins with static::DIRECTORY_PREVIOUS, which will voilate the root
   */
  public static function path( $path ) {

    if( !isset( self::$cache[ $path ] ) ) {

      // deep normalization if the path contains one-dir-up segment (clean multiple separators)
      $_path = preg_replace( '#\\' . static::DIRECTORY_SEPARATOR . '+#', static::DIRECTORY_SEPARATOR, ltrim( $path, static::DIRECTORY_SEPARATOR ) );

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

      // check for root voilation
      if( preg_match( addcslashes( '#^' . static::DIRECTORY_PREVIOUS . '(' . static::DIRECTORY_SEPARATOR . '|$)#', './' ), $_path ) ) {
        throw new SystemExceptionPathInvalid( $path );
      }

      self::$cache[ $path ] = $_path;
    }

    return self::$cache[ $path ];
  }
  /**
   * Force path to be a directory
   *
   * ..and ensure the trailing separator (except for root path)
   *
   * @param string $path
   *
   * @return string
   */
  public static function directory( $path ) {
    return ltrim( rtrim( $path, static::DIRECTORY_SEPARATOR ) . static::DIRECTORY_SEPARATOR, static::DIRECTORY_SEPARATOR );
  }
}

/**
 * Interface for all File related exceptions
 *
 * @package Framework\File
 */
interface SystemExceptionInterface extends Framework\ExceptionInterface {
}
/**
 * Basic file operation failures
 *
 * @package Framework\File
 */
class SystemException extends Exception\Runtime implements SystemExceptionInterface {

  const ID = '32#framework';

  /**
   * @param string          $path
   * @param mixed           $error
   * @param \Throwable|null $previous
   */
  public function __construct( $path, $error, \Throwable $previous = null ) {

    $data = [ 'path' => $path, 'error' => $error ];
    parent::__construct( Text::insert( 'Failed file operation for \'{path}\'', $data ), static::ID, $data, $previous );
  }
}
/**
 * Missing, not a directory or not readable root path
 *
 * @package Framework\File
 */
class SystemExceptionRootInvalid extends Exception\Runtime implements SystemExceptionInterface {

  const ID = '36#framework';

  /**
   * @param string $path The invalid path
   */
  public function __construct( $path ) {

    $data = [ 'path' => $path ];
    parent::__construct(
      Text::insert( 'Missing or invalid filesystem root: \'{path}\'', $data ),
      static::ID,
      $data,
      null,
      Framework\Application::SEVERITY_WARNING
    );
  }
}
/**
 * The given path is outside the root
 *
 * @package Framework\File
 */
class SystemExceptionPathInvalid extends Exception\Runtime implements SystemExceptionInterface {

  const ID = '35#framework';

  /**
   * @param string $path The invalid path
   */
  public function __construct( $path ) {
    $data = [ 'path' => $path ];
    parent::__construct( Text::insert( 'Path is outside the root: \'{path}\'', $data ), static::ID, $data, null, Framework\Application::SEVERITY_WARNING );
  }
}
/**
 * The path's type is not suitable for the operation
 *
 * @package Framework\File
 */
class SystemExceptionTypeInvalid extends Exception\Logic implements SystemExceptionInterface {

  const ID = '34#framework';

  /**
   * @param string $path
   * @param array  $allow Allowed path types
   */
  public function __construct( $path, array $allow ) {

    $data = [ 'path' => $path, 'allow' => implode( ',', $allow ) ];
    parent::__construct(
      Text::insert( 'Path (\'{path}\') must be {allow} for this operation', $data ),
      static::ID,
      $data,
      null,
      Framework\Application::SEVERITY_NOTICE
    );
  }
}
/**
 * Don't have enough permission for the operation
 *
 * @package Framework\File
 */
class SystemExceptionPermission extends Exception\Runtime implements SystemExceptionInterface {

  const ID = '33#framework';

  /**
   * @param string $path
   * @param string $allow Required permission meta name
   */
  public function __construct( $path, $allow ) {

    $data = [ 'path' => $path, 'allow' => $allow ];
    parent::__construct(
      Text::insert( 'Failed operation, due to insufficient permission for \'{path}\'', $data ),
      static::ID,
      $data,
      null,
      Framework\Application::SEVERITY_CRITICAL
    );
  }
}
