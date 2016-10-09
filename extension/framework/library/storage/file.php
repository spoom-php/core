<?php namespace Framework\Storage;

use Framework\Exception;
use Framework\Helper\ConverterInterface;

/**
 * Class File
 * @package Framework\Storage
 *
 * @since   0.6.0
 *
 * TODO implement multi storage null namespace support (synced namespace save/load/remove)
 *
 * @property-read string $path  The path base for the storage
 */
class File extends Permanent {

  /**
   * Empty namespace operation for a multi storage
   */
  const EXCEPTION_INVALID_NAMESPACE = 'framework#11N';
  /**
   * Can't write the path due to system restrictions. Arguments:
   *  - path [string]: The path
   *  - error [string]: The php last error message
   */
  const EXCEPTION_INVALID_WRITE = 'framework#12W';
  /**
   * The write operation was unsuccessful. Arguments:
   *  - path [string]: The path
   *  - error [string]: The php last error message
   */
  const EXCEPTION_FAIL_WRITE = 'framework#13E';
  /**
   * The path is not readable. Arguments:
   *  - path [string]: The path
   */
  const EXCEPTION_INVALID_READ = 'framework#14W';
  /**
   * The read operation was unsuccessful. Arguments:
   *  - path [string]: The path
   *  - error [string]: The php last error message
   */
  const EXCEPTION_FAIL_READ = 'framework#15E';
  /**
   * The path is not writeable. Arguments:
   *  - path [string]: The path
   */
  const EXCEPTION_INVALID_DESTROY = 'framework#16W';
  /**
   * The unlink operation was unsuccessful. Arguments:
   *  - path [string]: The path
   *  - error [string]: The php last error message
   */
  const EXCEPTION_FAIL_DESTROY = 'framework#17E';
  /**
   * Missing or invalid path definition
   */
  const EXCEPTION_MISSING_PATH = 'framework#18W';
  /**
   * Can't remove the remained file after format change. Arguments:
   * - path [string]: The path
   * - meta [PermanentMeta]: The metadata for the remain file
   */
  const EXCEPTION_FAIL_CLEAN = 'framework#19N';

  /**
   * Base for the path
   *
   * @var string
   */
  private $_base;

  /**
   * Directory path for the source
   *
   * @var string
   */
  protected $_path;

  /**
   * @param string|null          $path       File or directory path base for the storage. The file MUST be without dot and extension and directories MUST end with '/'
   * @param ConverterInterface[] $converters Default converters for the permanent storage. The first converter will be the default format
   * @param int|string           $base       The base of the path
   */
  public function __construct( $path, $converters = [], $base = \Framework::PATH_BASE ) {
    $this->_path = $path;
    $this->_base = (string) $base;

    parent::__construct( null, $this->isMulti() ? 'default' : null, self::CACHE_NONE, $converters );
  }

  /**
   * @inheritdoc
   *
   * @return $this
   * @throws Exception
   */
  public function save( $namespace = null, $format = null ) {

    // save previous file data for later
    $previous = null;
    if( isset( $this->converter_cache[ $namespace ] ) ) try {

      $previous       = (object) [
        'exist' => false,
        'meta'  => $this->converter_cache[ $namespace ]
      ];
      $previous->path = $this->getFile( $namespace, $previous->meta->getFormat(), $previous->exist );

    } catch( \Exception $e ) {
      Exception\Helper::wrap( $e )->log();
    }

    // do the saving like normal
    parent::save( $namespace, $format );
    if( !$this->getException() ) {

      // clean the previous file, if there is no need for it
      if( !empty( $previous->exist ) && $previous->meta != $this->converter_cache[ $namespace ] ) try {

        $this->destroyFile( $previous->path );

      } catch( \Exception $e ) {
        $this->setException( new Exception\System( self::EXCEPTION_FAIL_CLEAN, [
          'path' => $previous->path,
          'meta' => $previous->meta
        ], $e ) );
      }
    }
    return $this;
  }

  /**
   * Write namespace content to the corresponding file resource
   *
   * @param string      $content
   * @param string|null $namespace
   *
   * @throws Exception
   */
  protected function write( $content, $namespace = null ) {

    $exist     = false;
    $converter = $this->converter_cache[ $namespace ];
    $path      = $this->getFile( $namespace, $converter->getFormat(), $exist );

    $this->writeFile( $path, $content, 0755 );
  }
  /**
   * Read the namespace file resource
   *
   * @param string|null $namespace
   *
   * @return null|string
   * @throws Exception
   */
  protected function read( $namespace = null ) {

    $exist = false;
    $path  = $this->getFile( $namespace, null, $exist );
    if( !$exist || !is_file( $this->_base . $path ) ) return null;
    else {

      $result                              = $this->readFile( $path );
      $this->converter_cache[ $namespace ] = $this->getConverter()->get( mb_strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) );

      return $result;
    }
  }
  /**
   * Destroy (remove) the namespace file resource and all contents
   *
   * @param string|null $namespace
   *
   * @throws Exception
   */
  protected function destroy( $namespace = null ) {

    $exist = false;
    $path  = $this->getFile( $namespace, null, $exist );
    if( $exist ) $this->destroyFile( $path );
  }

  /**
   * Get file path by namespace
   *
   * @param string      $namespace The namespace
   * @param string|null $format    Force extension for the file
   * @param bool        $exist     Indicates the returned path existance
   *
   * @return null|string
   * @throws Exception
   */
  protected function getFile( $namespace, $format = null, &$exist = false ) {

    if( !$this->_path ) throw new Exception\Strict( self::EXCEPTION_MISSING_PATH );
    else {

      if( !$this->isMulti() ) $path = $this->_path . '.';
      else if( $namespace === null ) throw new Exception\Strict( self::EXCEPTION_INVALID_NAMESPACE );
      else $path = $this->_path . $namespace . '.';

      if( $format ) $file = $path . $format;
      else {

        $file = glob( $this->_base . $path . '*' );
        if( !count( $file ) ) $file = null;
        else $file = str_replace( $this->_base, '', array_shift( $file ) );
      }

      $exist = $file && is_file( $this->_base . $file );
      return $file;
    }
  }

  /**
   * Write a content to a file
   *
   * @param string $path       The path to the file without the _PATH_BASE_PATH_BASE
   * @param string $content    The content to write
   * @param int    $permission Default permissions for the created directories
   *
   * @throws Exception\System
   */
  protected function writeFile( $path, $content, $permission = 0777 ) {

    // check directory and file existance and writeability
    $directory = pathinfo( $this->_base . $path, PATHINFO_DIRNAME ) . '/';
    if( !is_dir( $directory ) && @!mkdir( $directory, $permission, true ) ) {

      throw new Exception\System( self::EXCEPTION_INVALID_WRITE, [ 'path' => $path, 'error' => error_get_last() ] );

    } else if( ( !is_file( $this->_base . $path ) && @!touch( $this->_base . $path ) ) || !is_writeable( $this->_base . $path ) ) {

      throw new Exception\System( self::EXCEPTION_INVALID_WRITE, [ 'path' => $path, 'error' => error_get_last() ] );

    } else {

      // try to write the file
      $result = @file_put_contents( $this->_base . $path, $content );
      if( $result === false ) throw new Exception\System( self::EXCEPTION_FAIL_WRITE, [ 'path' => $path, 'error' => error_get_last() ] );
    }
  }
  /**
   * Read the file contents into a string
   *
   * @param string $path The path to the file without the _PATH_BASE
   *
   * @return string
   * @throws Exception\System
   */
  protected function readFile( $path ) {

    if( !is_readable( $this->_base . $path ) ) throw new Exception\System( self::EXCEPTION_INVALID_READ, [ 'path' => $path ] );
    else {

      // FIXME do not read large files! (or check for the memory)

      $content = @file_get_contents( $this->_base . $path );
      if( $content === false ) throw new Exception\System( self::EXCEPTION_FAIL_READ, [ 'path' => $path, 'error' => error_get_last() ] );
      else return $content;
    }
  }
  /**
   * Unlink a file
   *
   * @param string $path The path to the file without the _PATH_BASE
   *
   * @throws Exception\System
   */
  protected function destroyFile( $path ) {

    // check writeability
    if( !is_writeable( $this->_base . $path ) ) throw new Exception\System( self::EXCEPTION_INVALID_DESTROY, [ 'path' => $path ] );
    else {

      // unlink the file
      $result = @unlink( $this->_base . $path );
      if( !$result ) throw new Exception\System( self::EXCEPTION_FAIL_DESTROY, [ 'path' => $path, 'error' => error_get_last() ] );
    }
  }

  /**
   * Use namespaces or not (directory or simple file storage)
   *
   * @return bool
   */
  public function isMulti() {
    return $this->_path && $this->_path{strlen( $this->_path ) - 1} === '/';
  }
  /**
   * @return string
   */
  public function getPath() {
    return $this->_path;
  }

  /**
   * @since 0.6.4
   * @return string
   */
  public function getBase() {
    return $this->_base;
  }
  /**
   * @param string $value
   *
   * @since 0.6.4
   * @return File
   */
  public function setBase( $value ) {
    $this->_base = (string) $value;
    return $this;
  }
}
