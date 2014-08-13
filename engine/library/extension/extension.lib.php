<?php namespace Engine\Extension;

use Engine\Exception\Exception;
use Engine\Extension\Helper as ExtensionHelper;
use Engine\Utility\File;
use Engine\Utility\Library;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * One of the most important class in the engine. Handle all extension
 * stuffs eg: object creation, file getter, configuration and localization
 * managment and other
 *
 * Class Extension
 * @package Engine\Extension
 *
 * @property string              id
 * @property string              package
 * @property string              name
 * @property string|bool         extension
 * @property string              library
 *
 * @property Configuration       configuration
 * @property Localization        localization
 */
class Extension extends Library {

  const EXCEPTION_INVALID = 1;
  const EXCEPTION_MISSING = 2;

  const REGEXP_PACKAGE_NAME   = '[a-z]\\w+';
  const REGEXP_EXTENSION_NAME = '[a-z]\\w+';

  const DIRECTORY_LOCALIZATION  = 'localization';
  const DIRECTORY_CONFIGURATION = 'configuration';

  /**
   * Store extension id. It's the package and the
   * name separated by a dot
   *
   * @var string
   */
  private $_id;

  /**
   * Store extension package
   *
   * @var string
   */
  private $_package;

  /**
   * Store extension name
   *
   * @var string
   */
  private $_name;

  /**
   * Extension directory from root without _PATH
   *
   * @var string
   */
  private $_directory = null;

  /**
   * Handle configuration files for the extension
   *
   * @var Configuration
   */
  private $_configuration = null;

  /**
   * Handle localization files for the extension
   *
   * @var Localization
   */
  private $_localization = null;

  /**
   * Object constructor. Define directory of the object
   * and create config and localization objects.
   *
   * Throws error if manifest configuration file contains
   * different package or name from the given extension
   *
   * @param string|null $extension The extension package and name separated by a dot
   *
   * @throws \Exception
   */
  public function __construct( $extension = null ) {
    $this->_id = isset( $extension ) ? strtolower( $extension ) : $this->extension;

    // define directory
    $directory = ExtensionHelper::directory( $this->_id );
    if( !$directory ) throw new Exception( '.engine', self::EXCEPTION_MISSING, array( $extension ) );
    $this->_directory = $directory;

    // create and configure configuration object
    $this->_configuration = new Configuration( $this );
    $this->_localization  = new Localization( $this );

    // check manifest and save package and name
    $this->_package = $this->_configuration->gets( 'manifest:package', null );
    $this->_name    = $this->_configuration->gets( 'manifest:name' );
    if( $this->_name != '.engine' && "{$this->_package}.{$this->_name}" != $this->_id ) throw new Exception( '.engine', self::EXCEPTION_INVALID, array( $this->_id ) );
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
   * Get language string from the extension language object.
   * It's a proxy for Localization::getf() method
   *
   * @param string       $index
   * @param array|string $insertion
   *
   * @return string
   */
  public function text( $index, $insertion = null ) {
    return $this->_localization->getf( $index, $insertion );
  }

  /**
   * Get configuration variable from extension configuration object.
   * It's a proxy for Configuration::get() method
   *
   * @param string $index
   * @param mixed  $if_null
   *
   * @return mixed
   */
  public function option( $index, $if_null = null ) {
    return $this->_configuration->get( $index, $if_null );
  }

  /**
   * Return a directory path from the extension root directory
   *
   * @param string $route - dot separated route from extension root ( use backslash for dot in the route )
   * @param bool   $fpath - add _PATH constant or another ( or nothing )
   *
   * @return string
   */
  public function dir( $route = '', $fpath = false ) {
    $route = rtrim( $this->_directory . str_replace( '\.', '.', preg_replace( '/(?<!\\\\)\./', '/', $route ) ), '/' ) . '/';

    $base = $fpath === true ? _PATH : ( is_string( $fpath ) ? $fpath : '' );

    return $base . $route;
  }

  /**
   * Return a file ( or file list ) from the extension root directory
   *
   * @param string  $file_name - The file name ( use | and | for regexp file filter or * for dir list
   * @param string  $route     - dot separated route from extension root ( use backslash for dot in the route )
   * @param boolean $fpath     - add _PATH constant or another ( or nothing )
   *
   * @return bool|string
   */
  public function file( $file_name, $route = '', $fpath = false ) {
    $route = $this->dir( $route, $fpath );

    // file list
    if( $file_name == '*' || $file_name{0} == '|' ) {
      $files = File::getList( $route, false, $file_name == '*' ? false : preg_replace( '/(^\\||\\|$)/', '/', $file_name ) );

      foreach( $files as &$f ) $f = $route . $f;

      return $files;

    }
    else return $route . $file_name;
  }

  /**
   * Get the first exist library name or return an Exception
   *
   * @param string $class_name - String with dot separated namespace ( exclude Package\Name\ ) or an array of this strings ( return the first exist )
   *
   * @return string|false
   */
  public function library( $class_name ) {
    if( !is_array( $class_name ) ) $class_name = array( $class_name );

    $base = ( isset( $this->_package ) ? ( $this->_package . '\\' ) : '' ) . $this->_name . '\\';
    foreach( $class_name as $name ) {
      $class = $base . str_replace( '.', '\\', $name );

      if( class_exists( $class, true ) ) return $class;
    }

    return false;
  }

  /**
   * Triggers an event of the extension and return the event object for the result
   *
   * @param string $event the dot separated event name
   * @param array $arguments arguments passed to the event handlers
   *
   * @return Event
   */
  public function trigger( $event, $arguments = array() ) {
    $event = new Event( $this, $event, $arguments );
    return $event->execute();
  }

  /**
   * Get extension library class instance with given param.
   *
   * @param string|array $class_name String with dot separated namespace ( exclude Package\Name\ ) or an array of this strings ( return the first exist )
   * @param mixed        $param      Array of params added to the contructor but NOT as a param list!
   *
   * @return mixed
   */
  public function instance( $class_name, $param = null ) {
    $class = $this->library( $class_name );

    if( !$class ) return null;

    $instance = isset( $param ) ? new $class( $param ) : new $class();

    return $instance;
  }
}