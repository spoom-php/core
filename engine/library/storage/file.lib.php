<?php namespace Engine\Storage;

use Engine\Utility\Enumerable;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class File
 * @package Engine\Storage
 *
 * @property string directory
 * @property string default
 * @property array allow
 */
class File extends Simple {

  /**
   * Global storage for file contents ( parsed )
   *
   * @var array
   */
  private static $files = array();

  /**
   * Meta properties for files
   *
   * @var array
   */
  private static $meta = array();

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
   * "Cache" for already loaded namespaces
   *
   * @var array
   */
  private $loaded = array();

  /**
   * Set given directory to handle
   *
   * @param string $directory
   * @param mixed $allow
   */
  public function __construct( $directory, $allow = array( 'php', 'ini', 'json', 'xml' ) ) {
    parent::__construct();

    $this->_directory = rtrim( $directory, '\\/' ) . '/';
    $this->_allow = is_array( $allow ) ? $allow : array( @(string) $allow );
  }

  /**
   * @param $index
   *
   * @return mixed
   */
  public function __get( $index ) {
    $i = '_' . $index;
    if( property_exists( $this, $i ) ) return $this->{$i};
    else return parent::__get( $index );
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
   * @param string $index
   *
   * @return bool
   */
  public function __isset( $index ) {
    return property_exists( $this, '_' . $index ) || parent::__isset( $index );
  }

  /**
   * Save modified namespace to file.
   *
   * @param string $namespace
   * @param null $extension
   * @param int $permission
   *
   * @return File
   */
  public function save( $namespace, $extension = null, $permission = 0777 ) {
    $filename = $this->file( $namespace );
    $index = $this->_directory . $namespace;

    // if namespace not exists then try to remove the file to
    if( !$this->exist( $namespace . ':' ) ) {

      if( $filename && is_writeable( $filename ) ) {
        unset( self::$files[ $index ] );
        return @unlink( $filename );
      }

      // save the content to the file
    } else if( isset( self::$files[ $index ] ) ) {

      if( $filename ) $extension = pathinfo( $filename, PATHINFO_EXTENSION );
      else {

        $extension = in_array( $extension, $this->_allow ) ? $extension : $this->_default;
        if( !is_dir( $this->_directory ) && @!mkdir( $this->_directory, $permission, true ) ) return false;
        else $filename = $this->_directory . $namespace . '.' . $extension;
      }

      $content = self::$files[ $index ];
      $method = 'convert' . ucfirst( strtolower( $extension ) );

      if( ( is_file( $filename ) || @touch( $filename ) ) && is_writeable( $filename ) && method_exists( $this, $method ) ) {

        $result = $this->{$method}( $content, $namespace, self::$meta[ $index ] );
        if( trim( $result ) != '' ) return @file_put_contents( $filename, $result );
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
   * @return File
   */
  protected function load( $namespace ) {

    $filename = $this->file( $namespace );
    $index = $this->_directory . $namespace;
    if( !$filename ) self::$files[ $index ] = self::$meta[ $index ] = array();
    else if( is_file( $filename ) && is_readable( $filename ) ) {

      // if not exist load to the cache
      if( !isset( self::$files[ $index ] ) ) {

        $extension = pathinfo( $filename, PATHINFO_EXTENSION );
        $method = 'convert' . ucfirst( strtolower( $extension ) );
        self::$files[ $index ] = self::$meta[ $index ] = array();

        if( method_exists( $this, $method ) ) {
          $content = @file_get_contents( $filename );
          $result = $this->{$method}( $content, $namespace, self::$meta[ $index ] );

          self::$files[ $index ] = (array) $result;
        }
      }
    }

    // set storage namespace to point this container
    $this->addr( self::$files[ $index ], $namespace );

    return $this;
  }

  /**
   * Get file path by namespace, returns false
   * if file not exist
   *
   * @param string $namespace
   *
   * @return mixed
   */
  protected function file( $namespace ) {

    if( $this->_directory ) {

      $filename = $namespace;
      $directory = $this->_directory;
      $path = $directory . $filename . '.';

      if( is_dir( $directory ) ) foreach( $this->_allow as $t ) {
        if( is_file( $path . $t ) && is_readable( $path . $t ) ) return $path . $t;
      }
    }

    return false;
  }

  /**
   * @param \stdClass $index
   * @param bool $build
   *
   * @see Simple::search
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
   * Php is a typesecure storage type. What type comes to write out, it will came back as the same type. Other advantage is
   * the data security: No one can see the contents from outside the server, even if the server missconfigured for other storage types.
   * Major disadvantage of this file type is the human read/write ability.
   *
   * note: Can't serialize resources!
   *
   * @param mixed $content
   * @param string $namespace
   * @param array $meta
   *
   * @return mixed
   */
  protected function convertPhp( $content, $namespace, array &$meta ) {
    if( is_string( $content ) ) return unserialize( preg_replace( '/^(<\\?php\\s*\\/\\*{)/i', '', preg_replace( '/(}\\s*\\*\\/)$/i', '', $content ) ) );
    else return '<?php /*{' . serialize( $content ) . '}*/';

  }

  /**
   * A really old school file type for configuration, but it works ( in some cases ). It's only for some compatibility,
   * new configuration files should not be stored in this type.
   * However, multi array structure converted ( and parsed back ) into dot separated keys and no support for
   * sections ( can't convert it back easily so don't bother with it anyway ).
   *
   * @param mixed $content
   * @param string $namespace
   * @param array $meta
   *
   * @return mixed
   */
  protected function convertIni( $content, $namespace, array &$meta ) {

    // read from the ini string
    $result = array();
    if( is_string( $content ) ) {

      $ini = parse_ini_string( $content, false );
      if( is_array( $ini ) ) foreach( $ini as $key => $value ) {
        $keys = explode( '.', $key );
        $arr = & $result;

        while( $key = array_shift( $keys ) ) $arr = & $arr[ $key ];
        $arr = $value;
      }

      // write out ini file
    } else {

      $iterator = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $content ) );
      foreach( $iterator as $value ) {
        $keys = array();
        foreach( range( 0, $iterator->getDepth() ) as $depth ) $keys[ ] = $iterator->getSubIterator( $depth )->key();

        $print = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;
        $quote = is_numeric( $value ) || is_bool( $value ) ? '' : ( !strpos( $value, '"' ) ? '"' : "'" );
        $result[ ] = join( '.', $keys ) . "={$quote}{$print}{$quote}";
      }

      $result = implode( "\n", $result );
    }

    return $result;
  }

  /**
   * Convert configuration multi array into json and back. Write out in human readable format, but don't support any comment type.
   * Maybe in the next releases. This is the default and prefered configuration type.
   *
   * @param mixed $content
   * @param string $namespace
   * @param array $meta
   *
   * @return mixed
   */
  protected function convertJson( $content, $namespace, array &$meta ) {
    $return = null;

    if( !is_string( $content ) ) $return = Enumerable::toJson( $content, true );
    else {

      $return = Enumerable::fromJson( $content, !$this->prefer_object );
      if( $return ) $return = (array) $return;
    }

    return $return;
  }

  /**
   * @param mixed $content
   * @param string $namespace
   * @param array $meta
   *
   * @return mixed
   */
  protected function convertXml( $content, $namespace, array &$meta ) {

    if( !is_string( $content ) ) return Enumerable::toXml( $content, isset( $meta['attribute'] ) ? $meta['attribute'] : array(), $namespace, isset( $meta['version'] ) ? $meta['version'] : '1.0', isset( $meta['encoding'] ) ? $meta['encoding'] : 'UTF-8' )->asXml();
    else {

      $meta[ 'attribute' ] = array();
      $meta[ 'version' ] = null;
      $meta[ 'encoding' ] = null;
      $object = Enumerable::fromXml( $content, $meta[ 'attribute' ], $meta[ 'version' ], $meta[ 'encoding' ] );

      return $object;
    }
  }
}