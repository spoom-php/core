<?php namespace Spoom\Core;

use Spoom\Core\Helper\Stream;
use Spoom\Core\Helper\StreamInterface;
use Spoom\Core;
use Spoom\Core\Helper\Text;

/**
 * Interface FileInterface
 */
interface FileInterface {

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
   * Path to the file (proxy to ->getPath(true))
   *
   * @return string
   */
  public function __toString(): string;

  /**
   * Clear the internal path or meta caches
   *
   * @return $this
   */
  public function reset();

  /**
   * Check if the path is exists, with the given meta data
   *
   * @param array $meta Optional metadata checks
   *
   * @return bool
   * @throws FileException Unsuccessful operation, due to the underlying system
   */
  public function exist( array $meta = [] ): bool;
  /**
   * Create a stream for read/write operations
   *
   * @param int   $mode
   * @param array $meta
   *
   * @return StreamInterface
   */
  public function stream( int $mode = StreamInterface::MODE_READ, array $meta = [] ): StreamInterface;

  /**
   * Get a sub-path or path from the root
   *
   * @param string $path
   * @param bool   $relative Relative from the path, or absolute from the root
   *
   * @return FileInterface
   */
  public function get( string $path, bool $relative = true ): FileInterface;
  /**
   * List directory contents
   *
   * @param string|null $pattern   Search pattern if any
   * @param bool        $recursive List subdirectory contents
   * @param bool        $directory Include or exclude directories
   *
   * @return FileInterface[]
   * @throws FileExceptionTypeInvalid If the path is a file not a directory
   * @throws FileExceptionPermission If the path is not readable
   * @throws FileException Unsuccessful operation, due to the underlying system
   */
  public function search( ?string $pattern = null, bool $recursive = false, bool $directory = true ): array;
  /**
   * Create an empty directory or file
   *
   * @param array $meta The newly created path meta
   *
   * @return FileInterface
   * @throws FileExceptionPermission If the path is not writeable
   * @throws FileException Unsuccessful operation, due to the underlying system
   */
  public function create( array $meta = [] ): FileInterface;
  /**
   * Remove a directory or a file
   *
   * This will remove the directory AND ALL the contents in it
   *
   * @throws FileExceptionPermission If the path is not writeable
   * @throws FileException Unsuccessful operation, due to the underlying system
   */
  public function remove();

  /**
   * Copy the content (or a directory with sub-content) to the destination
   *
   * Overwrites the destination silently
   *
   * @param string $destination
   * @param bool   $move Remove the source after the successful copy
   *
   * @return FileInterface
   * @throws FileExceptionPermission If the path is not readable or the destination is not writeable
   * @throws FileException Unsuccessful operation, due to the underlying system
   */
  public function copy( string $destination, bool $move = false ): FileInterface;

  /**
   * Get the relative path
   *
   * @param bool $real Get the "real" and absolute path, or the normalized path within the system
   *
   * @return string
   */
  public function getPath( bool $real = false ): string;
  /**
   * Set the relative path
   *
   * @param string $value
   *
   * @return static
   */
  public function setPath( string $value );
  /**
   * Get a (or all) meta for a specific path
   *
   * @param string[]|string|null $names
   *
   * @return array|mixed
   * @throws FileException Unsuccessful operation, due to the underlying system
   */
  public function getMeta( $names = null );
  /**
   * Set a path metadata, if not read only
   *
   * @param mixed       $value
   * @param string|null $name
   *
   * @throws FileException Unsuccessful operation, due to the underlying system
   */
  public function setMeta( $value, ?string $name = null );

  /**
   * @return string
   */
  public function getRoot(): string;
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
 *
 * @property      string $path
 * @property      array  $meta
 * @property-read string $root
 * @property-read bool   $directory
 * @property-read bool   $readable
 * @property-read bool   $writeable
 */
class File implements FileInterface {

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
  private static $cache_path = [];
  /**
   * Cache paths meta values (key is the standard path, value is the cached meta values)
   *
   * @var array[]
   */
  private static $cache_meta = [];

  /**
   * @var string
   */
  private $_root;
  /**
   * @var string
   */
  private $_path;

  /**
   *
   * @param string $root The root path for the filesystem. It MUST exists!
   * @param string $path
   *
   * @throws FileExceptionRootInvalid Invalid root path
   */
  public function __construct( string $root, string $path = '' ) {

    $_root = is_link( $root ) ? realpath( $root ) : $root;
    if( $_root === false || !is_dir( $_root ) || !is_readable( $_root ) ) throw new FileExceptionRootInvalid( $root );
    else {

      $this->_root = rtrim( $_root, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
      $this->_path = $path;
    }
  }

  //
  public function __toString(): string {
    return $this->getPath( true );
  }

  //
  public function reset() {

    clearstatcache();
    static::$cache_path[ $this->_root ] = static::$cache_meta[ $this->_root ] = [];

    return $this;
  }

  //
  public function exist( array $meta = [] ): bool {
    $_path = $this->getPath( true );

    $result = file_exists( $_path );
    if( $result && !empty( $meta ) ) {
      // TODO check agains the metadata array
    }

    return $result;
  }

  //
  public function stream( int $mode = StreamInterface::MODE_READ, array $meta = [] ): StreamInterface {

    $_meta = $this->getMeta();
    if( $_meta[ static::META_TYPE ] != static::TYPE_FILE ) throw new FileExceptionTypeInvalid( $this->getPath( true ), [ static::TYPE_FILE ] );
    else if( $mode & StreamInterface::MODE_WRITE && !$_meta[ static::META_PERMISSION_WRITE ] ) {
      throw new FileExceptionPermission( $this->getPath( true ), static::META_PERMISSION_WRITE );
    } else if( $mode & StreamInterface::MODE_READ && !$_meta[ static::META_PERMISSION_READ ] ) {
      throw new FileExceptionPermission( $this->getPath( true ), static::META_PERMISSION_READ );
    } else {

      // create the the file before write
      if( $mode & StreamInterface::MODE_WRITE ) {

        // TODO add default meta for the silent directory creation
        $this->create( $meta );
      }

      // create write operation pointer
      try {
        return new Stream( $this->exist() ? $this->getPath( true ) : 'php://memory', $mode );
      } catch( \InvalidArgumentException $e ) {
        throw new FileException( $this->getPath( true ), error_get_last(), $e );
      }
    }
  }

  //
  public function get( string $path, bool $relative = true ): FileInterface {

    $result = clone $this;
    if( !empty( $path ) ) {
      $result->setPath( ( $relative ? static::directory( $this->getPath() ) : '' ) . $path );
    }

    return $result;
  }
  //
  public function search( ?string $pattern = null, bool $recursive = false, bool $directory = true ): array {

    $meta = $this->getMeta();
    if( $meta[ static::META_TYPE ] != static::TYPE_DIRECTORY ) throw new FileExceptionTypeInvalid( $this->getPath( true ), [ static::TYPE_DIRECTORY ] );
    else if( !$meta[ static::META_PERMISSION_READ ] ) throw new FileExceptionPermission( $this->getPath( true ), static::META_PERMISSION_READ );
    else if( !$this->exist() ) return [];
    else {

      $result = [];
      $_path  = $this->getPath( true );

      $list = scandir( $_path );
      foreach( $list as $tmp ) {
        if( !in_array( $tmp, [ '.', '..' ] ) && ( !$pattern || preg_match( $pattern, $tmp ) ) ) {

          $file         = $this->get( $tmp );
          $is_directory = $file->getMeta( static::META_TYPE ) == static::TYPE_DIRECTORY;
          if( $is_directory ) $file->setPath( static::directory( $file->getPath() ) );

          if( ( $directory || !$is_directory ) ) {
            $result[] = $file;
          }

          // search in subdirectory
          if( $recursive && $is_directory ) {
            $result = array_merge( $result, $file->search( $pattern, $recursive, $directory ) );
          }
        }
      }

      return $result;
    }
  }
  //
  public function create( array $meta = [] ): FileInterface {

    if( !$this->exist() ) {

      // check for permissions
      $_meta = $this->getMeta();
      if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new FileExceptionPermission( $this->getPath( true ), static::META_PERMISSION_WRITE );
      else {

        $_path = $this->getPath( true );
        if( $_meta[ static::META_TYPE ] == static::TYPE_DIRECTORY ) $result = @mkdir( $_path, $_meta[ static::META_PERMISSION ], true );
        else {

          // create the directory
          // TODO add default meta for the silent directory creation
          $this->get( static::directory( dirname( $this->getPath() ) ), false )->create( $meta );

          $result = @touch( $_path );
        }

        if( !$result ) throw new FileException( $this->getPath( true ), error_get_last() );
        else {

          // apply metadata for the new path
          $this->setMeta( $meta );
        }
      }
    }

    return $this;
  }
  //
  public function remove() {
    if( $this->exist() ) {

      // check for permissions
      $_meta = $this->getMeta();
      if( !$_meta[ static::META_PERMISSION_WRITE ] ) throw new FileExceptionPermission( $this->getPath( true ), static::META_PERMISSION_WRITE );
      else {

        $_path = $this->getPath( true );
        if( $_meta[ static::META_TYPE ] != static::TYPE_DIRECTORY ) $result = @unlink( $_path );
        else {

          // remove contents first
          $list = $this->search();
          foreach( $list as $file ) {
            $file->remove();
          }

          // remove the directory itself lasts
          $result = @rmdir( $_path );
        }

        if( !$result ) {
          throw new FileException( $this->getPath( true ), error_get_last() );
        }
      }
    }
  }

  //
  public function copy( string $destination, bool $move = false ): FileInterface {

    $destination = new static( $this->getRoot(), $destination );
    if( $this->exist() ) {

      // check for permissions
      $_meta             = $this->getMeta();
      $_meta_destination = $destination->getMeta();
      if( !$_meta[ static::META_PERMISSION_READ ] ) throw new FileExceptionPermission( $this->getPath( true ), static::META_PERMISSION_READ );
      else if( !$_meta_destination[ static::META_PERMISSION_WRITE ] ) {
        throw new FileExceptionPermission( $destination->getPath( true ), static::META_PERMISSION_WRITE );
      } else if( $_meta_destination[ static::META_TYPE ] != $_meta[ static::META_TYPE ] ) {
        throw new FileExceptionTypeInvalid( $destination->getPath( true ), [ $_meta[ static::META_TYPE ] ] );
      } else {

        $_path        = $this->getPath( true );
        $_destination = $destination->getPath( true );
        if( $move ) $result = @rename( $_path, $_destination );
        else {

          // handle simple file copy
          if( $_meta[ static::META_TYPE ] != static::TYPE_DIRECTORY ) $result = @copy( $_path, $_destination );
          else {

            // copy base directory
            $result = $destination->create();
            if( $result ) {

              // copy all contents from the directory
              $list = $this->search();
              foreach( $list as $file ) {
                $file->copy( static::directory( $destination->getPath() ) . substr( $file->getPath(), strlen( $this->getPath() ) ) );
              }
            }
          }
        }

        if( !$result ) throw new FileException( $this->getPath( true ), error_get_last() );
        else {

          $destination->setMeta( $_meta );
        }
      }
    }

    return $destination;
  }

  //
  public function getPath( bool $real = false ): string {

    if( !$real ) return $this->_path;
    else if( empty( $this->_path ) || $this->_path == static::DIRECTORY_SEPARATOR ) return $this->_root;
    else if( !isset( static::$cache_path[ $this->_root ][ $this->_path ] ) ) {

      // normalize path
      $_path = static::path( $this->_path );
      if( static::DIRECTORY_SEPARATOR != DIRECTORY_SEPARATOR ) {

        // replace standard directory separators to local system specific, if needed
        $_path = str_replace( static::DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $_path );
      }

      // resolve link
      $_path = $this->_root . $_path;
      if( is_link( $_path ) ) {
        $_path = realpath( $_path );
      }

      static::$cache_path[ $this->_root ]                 = empty( static::$cache_path[ $this->_root ] ) ? [] : static::$cache_path[ $this->_root ];
      static::$cache_path[ $this->_root ][ $this->_path ] = $_path;
    }

    return static::$cache_path[ $this->_root ][ $this->_path ];
  }
  //
  public function setPath( string $value ) {

    $this->_path = $value;
    return $this;
  }
  //
  public function getMeta( $names = null ) {

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
      if( !isset( static::$cache_meta[ $this->_root ][ $this->_path ][ $name ] ) ) {

        // determine path basic data for meta checks (only once)
        if( empty( $_path ) ) {

          $_path = $file = $this->getPath( true );
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
            else $meta = empty( $this->_path ) || $this->_path[ strlen( $this->_path ) - 1 ] == static::DIRECTORY_SEPARATOR ? static::TYPE_DIRECTORY
              : static::TYPE_FILE;

            break;

          case static::META_SIZE:

            if( !$exist ) $meta = 0;
            else if( $this->getMeta( static::META_TYPE ) == static::TYPE_FILE ) $meta = filesize( $_path );
            else {

              // calculate the size of a directory
              $meta = 0;
              $tmp  = $this->search();
              foreach( $tmp as $t ) {
                $meta += $t->getMeta( static::META_SIZE );
              }
            }

            break;

          case static::META_MIME:

            if( !$exist ) $meta = null;
            else {

              $tmp = class_exists( '\finfo' ) || function_exists( 'mime_content_type' );
              if( !$tmp ) throw new Core\ApplicationExceptionFeature( 'fileinfo', '0.1.0' );
              else if( !class_exists( '\finfo' ) ) $meta = mime_content_type( $_path );
              else {

                $info = new \finfo( FILEINFO_MIME_TYPE );
                $meta = $info->file( $_path );
                if( in_array( $meta, [ 'application/octet-stream', 'inode/x-empty' ] ) ) {
                  $meta = $info->buffer( $this->stream()->read() );
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
        static::$cache_meta[ $this->_root ]                          = static::$cache_meta[ $this->_root ] ?? [];
        static::$cache_meta[ $this->_root ][ $this->_path ]          = static::$cache_meta[ $this->_root ][ $this->_path ] ?? [];
        static::$cache_meta[ $this->_root ][ $this->_path ][ $name ] = $meta;
      }

      // extend the result with the meta
      $result[ $name ] = static::$cache_meta[ $this->_root ][ $this->_path ][ $name ];
    }

    return $result_name ? ( $result[ $result_name ] ?? null ) : $result;
  }
  //
  public function setMeta( $value, ?string $name = null ) {
    $_path = $this->getPath( true );

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

  //
  public function getRoot(): string {
    return $this->_root;
  }
  //
  public function isDirectory(): bool {
    return $this->getMeta( static::META_TYPE ) == static::TYPE_DIRECTORY;
  }
  //
  public function isReadable(): bool {
    return $this->getMeta( static::META_PERMISSION_READ );
  }
  //
  public function isWriteable(): bool {
    return $this->getMeta( static::META_PERMISSION_WRITE );
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
   * @throws FileExceptionPathInvalid The path begins with static::DIRECTORY_PREVIOUS, which will voilate the root
   */
  public static function path( string $path ): string {

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
        throw new FileExceptionPathInvalid( $path );
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
  public static function directory( string $path ): string {
    return ltrim( rtrim( $path, static::DIRECTORY_SEPARATOR ) . static::DIRECTORY_SEPARATOR, static::DIRECTORY_SEPARATOR );
  }
}

/**
 * Interface for all File related exceptions
 *
 */
interface FileExceptionInterface extends Core\ExceptionInterface {
}
/**
 * Basic file operation failures
 *
 */
class FileException extends Exception\Runtime implements FileExceptionInterface {

  const ID = '32#spoom-core';

  /**
   * @param string          $path
   * @param mixed           $error
   * @param \Throwable|null $previous
   */
  public function __construct( string $path, $error, ?\Throwable $previous = null ) {

    $data = [ 'path' => $path, 'error' => $error ];
    parent::__construct( Text::insert( 'Failed file operation for \'{path}\'', $data ), static::ID, $data, $previous );
  }
}
/**
 * Missing, not a directory or not readable root path
 *
 */
class FileExceptionRootInvalid extends Exception\Runtime implements FileExceptionInterface {

  const ID = '36#spoom-core';

  /**
   * @param string $path The invalid path
   */
  public function __construct( string $path ) {

    $data = [ 'path' => $path ];
    parent::__construct(
      Text::insert( 'Missing or invalid filesystem root: \'{path}\'', $data ),
      static::ID,
      $data,
      null,
      Core\Application::SEVERITY_WARNING
    );
  }
}
/**
 * The given path is outside the root
 *
 */
class FileExceptionPathInvalid extends Exception\Runtime implements FileExceptionInterface {

  const ID = '35#spoom-core';

  /**
   * @param string $path The invalid path
   */
  public function __construct( string $path ) {
    $data = [ 'path' => $path ];
    parent::__construct( Text::insert( 'Path is outside the root: \'{path}\'', $data ), static::ID, $data, null, Core\Application::SEVERITY_WARNING );
  }
}
/**
 * The path's type is not suitable for the operation
 *
 */
class FileExceptionTypeInvalid extends Exception\Logic implements FileExceptionInterface {

  const ID = '34#spoom-core';

  /**
   * @param string $path
   * @param array  $allow Allowed path types
   */
  public function __construct( string $path, array $allow ) {

    $data = [ 'path' => $path, 'allow' => implode( ',', $allow ) ];
    parent::__construct(
      Text::insert( 'Path (\'{path}\') must be {allow} for this operation', $data ),
      static::ID,
      $data,
      null,
      Core\Application::SEVERITY_NOTICE
    );
  }
}
/**
 * Don't have enough permission for the operation
 *
 */
class FileExceptionPermission extends Exception\Runtime implements FileExceptionInterface {

  const ID = '33#spoom-core';

  /**
   * @param string $path
   * @param string $allow Required permission meta name
   */
  public function __construct( string $path, string $allow ) {

    $data = [ 'path' => $path, 'allow' => $allow ];
    parent::__construct(
      Text::insert( 'Failed operation, due to insufficient permission for \'{path}\'', $data ),
      static::ID,
      $data,
      null,
      Core\Application::SEVERITY_CRITICAL
    );
  }
}
