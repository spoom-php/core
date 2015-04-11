<?php namespace Framework\Storage;

use Framework\Extension;
use Framework\Helper\Enumerable;
use Framework\Page;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Directory
 * @package Framework\Storage
 *
 * @property string $directory The directory base for the storage
 * @property string $default   The default extension for saving files
 * @property array  $allow     The allowed file extension to handle as namespace
 */
class Directory extends Multi {

  /**
   * Event called for (un)serialize the data to/from the file. Five arguments passed:
   *  - content [mixed]: The content to convert
   *  - type [int]: CONVERT_* constant
   *  - format [string]: The source data format (ini, json, xml..)
   *  - namespace [string]: The namespace for the data
   *  - &meta [array]: Store custom meta for a file (namespace) on unserialize to be able to serialize back again
   */
  const EVENT_CONVERT = 'storage.directory.convert';

  /**
   * Convert any type of data to string for store in file
   */
  const CONVERT_SERIALIZE = 0;
  /**
   * Convert string data to any type (mostly enumerable)
   */
  const CONVERT_UNSERIALIZE = 1;

  /**
   * Global storage for file contents ( parsed )
   *
   * @var array
   */
  private static $files = [ ];
  /**
   * Meta properties for files
   *
   * @var array
   */
  private static $meta = [ ];

  /**
   * "Cache" for already loaded namespaces
   *
   * @var array
   */
  private $loaded = [ ];

  /**
   * Allowed extensions for files
   *
   * @var array
   */
  protected $_allow;
  /**
   * Default extension when saving
   *
   * @var string
   */
  protected $_default = 'json';

  /**
   * Directory path for the source
   *
   * @var string
   */
  protected $_directory;

  /**
   * Set given directory to handle
   *
   * @param string $directory
   * @param mixed  $allow
   */
  public function __construct( $directory, $allow = [ 'php', 'ini', 'json', 'xml' ] ) {
    parent::__construct( 'default', null, self::CACHE_NONE );

    $this->_directory = $directory ? rtrim( $directory, '\\/' ) . '/' : null;
    $this->_allow = is_array( $allow ) ? $allow : [ @(string) $allow ];
  }

  /**
   * @param string $index
   *
   * @return mixed
   */
  public function __get( $index ) {

    $iindex = '_' . $index;
    if( property_exists( $this, $iindex ) ) return $this->{$iindex};
    else return parent::__get( $index );
  }
  /**
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index ) || parent::__isset( $index );
  }
  /**
   * Dynamic setter for privates
   *
   * @param string $index
   * @param mixed  $value
   */
  public function __set( $index, $value ) {
    switch( $index ) {
      case 'default':
        $this->_default = in_array( $value, $this->_allow ) ? (string) $value : $this->_default;
        break;
      default:
        parent::__set( $index, $value );
    }
  }

  /**
   * Save modified namespace to file
   *
   * @param string $namespace
   * @param null   $extension
   * @param int    $permission
   *
   * @return Directory
   */
  public function save( $namespace, $extension = null, $permission = 0777 ) {

    $exist = false;
    $path  = $this->path( $namespace, $extension, $exist );

    // if namespace not exists then try to remove the file to
    if( !$this->exist( $namespace . ':' ) ) {

      if( $exist && !is_writeable( _PATH_BASE . $path ) ) Page::getLog()->warning( 'Can\'t remove the \'{path}\' file!', [ 'namespace' => $namespace, 'path' => $path ], '\Framework\Storage\File' ); // log: warning
      else if( $exist ) {

        unset( self::$files[ $path ] );
        return @unlink( _PATH_BASE . $path );
      }

      // save the content to the file
    } else if( isset( self::$files[ $path ] ) ) {

      $directory = pathinfo( _PATH_BASE . $path, PATHINFO_DIRNAME ) . '/';
      if( !is_dir( $directory ) && @!mkdir( $directory, $permission, true ) ) {

        // log: warning
        Page::getLog()->warning( 'The \'{path}\' file directory not writeable!', [ 'namespace' => $namespace, 'directory' => $directory, 'path' => $path ], '\Framework\Storage\File' );
        return false;
      }

      $result = $this->process( self::$files[ $path ], self::CONVERT_SERIALIZE, $extension, $namespace, self::$meta[ $path ] );
      if( ( is_file( _PATH_BASE . $path ) || @touch( _PATH_BASE . $path ) ) && is_writeable( _PATH_BASE . $path ) ) return @file_put_contents( _PATH_BASE . $path, $result ) > 0;
      else {

        // log: warning
        Page::getLog()->warning( 'The \'{path}\' file not writeable!', [ 'namespace' => $namespace, 'path' => $path ], '\Framework\Storage\File' );
      }
    }

    return false;
  }
  /**
   * Load namespace ( file content ) to global storage and
   * set local storage namespace reference. Only load file
   * if isn't already loaded
   *
   * @param string $namespace
   *
   * @return Directory
   */
  protected function load( $namespace ) {

    $exist = false;
    $path  = $this->path( $namespace, null, $exist );
    if( !isset( self::$files[ $path ] ) ) {

      self::$files[ $path ] = self::$meta[ $path ] = [ ];
      if( $exist && is_file( _PATH_BASE . $path ) ) {

        if( !is_readable( _PATH_BASE . $path ) ) Page::getLog()->warning( 'The \'{path}\' file not readable!', [ 'namespace' => $namespace, 'path' => $path ], '\Framework\Storage\File' ); // log: warning
        else {

          self::$files[ $path ] = $this->process( @file_get_contents( _PATH_BASE . $path ), self::CONVERT_UNSERIALIZE, pathinfo( $path, PATHINFO_EXTENSION ), $namespace, self::$meta[ $path ] );
        }
      }
    }

    // set storage namespace to point this container
    $this->addr( self::$files[ $path ], $namespace );

    return $this;
  }

  /**
   * Get file path by namespace
   *
   * @param string      $namespace The namespace
   * @param string|null $extension Force extension for the file
   * @param bool        $exist     Indicates the returned path existance
   *
   * @return null|string
   */
  protected function path( $namespace, $extension = null, &$exist = false ) {

    if( $this->_directory ) {

      // define the used extension(s)
      if( in_array( $extension, $this->_allow ) ) $tmp = [ $extension ];
      else {

        $extension = $this->_default;
        $tmp       = $this->_allow;
      }

      // try to find the file
      $path = $this->_directory . $namespace . '.';
      if( is_dir( _PATH_BASE . $this->_directory ) ) foreach( $tmp as $t ) {
        if( is_file( _PATH_BASE . $path . $t ) && is_readable( _PATH_BASE . $path . $t ) ) {

          $exist = true;
          return $path . $t;
        }
      }

      // return a non exist path
      return $path . $extension; 
    }

    return null;
  }
  /**
   * @param \stdClass $index
   * @param bool      $build
   *
   * @see Advance::search
   *
   * @return object
   */
  protected function search( $index, $build = false ) {

    // try load the file container
    if( !isset( $this->loaded[ $index->namespace ] ) ) {
      $this->load( $index->namespace );

      // flag the namespace loaded
      $this->loaded[ $index->namespace ] = true;
    }

    // delegate problem to the parent
    return parent::search( $index, $build );
  }

  /**
   * Php is a typesecure storage type. What type comes to write out, it will came back as the same type. Other
   * advantage is the data security: No one can see the contents from outside the server, even if the server
   * missconfigured for other storage types. Major disadvantage of this file type is the human read/write ability.
   *
   * note: Can't serialize resources!
   *
   * @param mixed $content Content to convert
   * @param int   $type    CONVERT_* constant
   *
   * @return mixed
   */
  protected function convertPhp( $content, $type ) {
    if( $type == self::CONVERT_UNSERIALIZE ) return unserialize( preg_replace( '/^(<\\?php\\s*\\/\\*{)/i', '', preg_replace( '/(}\\s*\\*\\/)$/i', '', $content ) ) );
    else return '<?php /*{' . serialize( $content ) . '}*/';

  }
  /**
   * A really old school file type for configuration, but it works ( in some cases ). It's only for some compatibility,
   * new configuration files should not be stored in this type.
   * However, multi array structure converted ( and parsed back ) into dot separated keys and no support for
   * sections ( can't convert it back easily so don't bother with it anyway ).
   *
   * @param mixed $content Content to convert
   * @param int   $type    CONVERT_* constant
   *
   * @return mixed
   */
  protected function convertIni( $content, $type ) {

    // read from the ini string
    if( $type == self::CONVERT_UNSERIALIZE ) return Enumerable::fromIni( $content );
    else return Enumerable::toIni( $content );
  }
  /**
   * Convert configuration multi array into json and back. Write out in human readable format, but don't support any
   * comment type. Maybe in the next releases. This is the default and prefered configuration type.
   *
   * @param mixed $content Content to convert
   * @param int   $type    CONVERT_* constant
   *
   * @return mixed
   */
  protected function convertJson( $content, $type ) {
    $return = null;

    if( $type == self::CONVERT_SERIALIZE ) $return = Enumerable::toJson( $content, JSON_PRETTY_PRINT );
    else {

      $return = Enumerable::fromJson( $content, !$this->prefer );
      if( $return ) $return = (array) $return;
    }

    return $return;
  }
  /**
   * XML converter based on the Enumberable class xml related methods
   *
   * @see \Framework\Helper\Enumerable::toXml()
   * @see \Framework\Helper\Enumerable::fromXml()
   *
   * @param mixed  $content   Content to convert
   * @param int    $type      CONVERT_* constant
   * @param string $namespace The content source or target namespace
   * @param array  $meta      Custom meta storage that allow proper serialization (back) of the xml properties
   *
   * @return mixed
   */
  protected function convertXml( $content, $type, $namespace, array &$meta ) {

    if( $type == self::CONVERT_SERIALIZE ) return Enumerable::toXml( $content, isset( $meta[ 'attribute' ] ) ? $meta[ 'attribute' ] : [ ], $namespace, isset( $meta[ 'version' ] ) ? $meta[ 'version' ] : '1.0', isset( $meta[ 'encoding' ] ) ? $meta[ 'encoding' ] : 'UTF-8' )->asXml();
    else {

      $meta[ 'attribute' ] = [ ];
      $meta[ 'version' ]   = null;
      $meta[ 'encoding' ]  = null;
      $object              = Enumerable::fromXml( $content, $meta[ 'attribute' ], $meta[ 'version' ], $meta[ 'encoding' ] );

      return $object;
    }
  }

  /**
   * Process content for store or to handle in code. This will trigger the converter event or call a built-in converter
   * to do the work
   *
   * @param mixed  $content   Content to convert
   * @param int    $type      CONVERT_* constant
   * @param string $format    The source data type (json, xml, ini..)
   * @param string $namespace The content source or target namespace
   * @param array  $meta      Custom meta storage that allow the seralization and unserialization process to pass data
   *                          each other
   *
   * @return mixed
   */
  private function process( $content, $type, $format, $namespace, &$meta ) {

    $method = 'convert' . ucfirst( mb_strtolower( $format ) );
    if( method_exists( $this, $method ) ) return $this->{$method}( $content, $type, $namespace, $meta );
    else {

      $extension = new Extension( 'framework' );
      $event     = $extension->trigger(
        self::EVENT_CONVERT, [
          'content'   => $content,
          'type'      => $type,
          'format'    => $format,
          'namespace' => $namespace,
          'meta'      => &$meta
        ]
      );

      if( count( $event->result ) ) return $event->result[ 0 ];
      else {

        // log: warning
        Page::getLog()->warning( 'Missing converter for \'{format}\' type files', [
          'format' => $format, 'type' => $type, 'namespace' => $namespace
        ], '\Framework\Storage\File' );

        return $type == self::CONVERT_SERIALIZE ? '' : [ ];
      }
    }
  }
}
