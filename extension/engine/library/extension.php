<?php namespace Engine;

use Engine\Utility\File;
use Engine\Utility\Library;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * One of the most important class in the engine. Handle all extension
 * stuffs eg: object creation, file getter, configuration and localization
 * managment and other
 *
 * @package Engine
 *
 * @property string                        id
 *
 * @property Extension\Configuration       configuration
 * @property Extension\Localization        localization
 */
class Extension extends Library {

  /**
   * Exception code for invalid manifest data. This mostly indicates the extension package and name
   * missmatch in the manifest and the extension path. One data will be passed:
   *  - 0 [string]: Extension id
   */
  const EXCEPTION_CRITICAL_INVALID_MANIFEST = 'engine#4C';
  /**
   * Exception code for missing extension directory. One data will be passed:
   *  - 0 [string]: Extension id
   */
  const EXCEPTION_CRITICAL_MISSING_EXTENSION = 'engine#5C';

  /**
   * Exception code for invalid extension id
   */
  const EXCEPTION_NOTICE_INVALID_ID = 'engine#6N';

  /**
   * Default directory for localization files
   */
  const DIRECTORY_LOCALIZATION = 'localization/';
  /**
   * Default directory for configuration files
   *
   * warning: DO NOT TOUCH THIS! This directory is hardcoded in the autoloader
   */
  const DIRECTORY_CONFIGURATION = 'configuration/';

  /**
   * Store extension id. It's the package and the
   * name separated by a dash
   *
   * @var string
   */
  private $_id;

  /**
   * Extension directory from root without _PATH
   *
   * @var string
   */
  private $_directory = null;

  /**
   * Handle configuration files for the extension
   *
   * @var Extension\Configuration
   */
  private $_configuration = null;

  /**
   * Handle localization files for the extension
   *
   * @var Extension\Localization
   */
  private $_localization = null;

  /**
   * Object constructor. Define directory of the object
   * and create config and localization objects
   *
   * Throws error if manifest configuration file contains
   * different package or name from the given extension
   *
   * @param string|null $id The extension package and name separated by a dot
   *
   * @throws Exception\Strict On invalid extension id or invalid manifest data
   * @throws Exception\System On missing extension
   */
  public function __construct( $id = null ) {

    $this->_id = isset( $id ) ? mb_strtolower( $id ) : $this->extension;
    if( !Extension\Helper::validate( $this->_id ) ) throw new Exception\Strict( self::EXCEPTION_NOTICE_INVALID_ID, [ 'id' => $this->_id ] );
    else {

      // define directory
      $directory = Extension\Helper::directory( $this->_id );
      if( !$directory ) throw new Exception\System( self::EXCEPTION_CRITICAL_MISSING_EXTENSION, [ 'id' => $this->_id ] );
      else {

        $this->_directory = $directory;

        // create and configure configuration object
        $this->_configuration = new Extension\Configuration( $this );
        $this->_localization  = new Extension\Localization( $this );

        // check manifest and save package and name
        $package = $this->_configuration->gets( 'manifest:package' );
        $name    = $this->_configuration->gets( 'manifest:name' );
        $feature = $this->_configuration->gets( 'manifest:feature' );

        if( Extension\Helper::build( $package, $name, $feature ) != $this->_id ) {
          throw new Exception\Strict( self::EXCEPTION_CRITICAL_INVALID_MANIFEST, [ 'id' => $this->_id ] );
        }
      }
    }
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
   * Get language string from the extension language object. It's a proxy for Localization::getf() method
   *
   * @param string $index
   * @param array|string $insertion
   *
   * @return string
   */
  public function text( $index, $insertion = null ) {
    return $this->_localization->getf( $index, $insertion );
  }
  /**
   * Get configuration variable from extension configuration object. It's a proxy for Configuration::get() method
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
   * Return an extension directory. The return path is relative to the extension directory by default
   * (it can be modified with the $root parameter)
   *
   * @param string      $path Path from extension root
   * @param bool|string $root Add _PATH constant or another ( or nothing )
   *
   * @return string
   */
  public function directory( $path = '', $root = false ) {

    $base = $root === true ? _PATH : ( is_string( $root ) ? $root : '' );
    return $base . rtrim( $this->_directory . ltrim( $path, '/' ), '/' ) . '/';
  }
  /**
   * Return an extension file ( or file list ). The return file path is relative to the extension directory by default
   * (it can be modified with the $root parameter)
   *
   * @param string  $file_name The file name ( use | and | for regexp file filter or * for directory listing )
   * @param string  $path      Path from the extension root
   * @param boolean $root      Add _PATH constant or another prefix for the path ( or nothing )
   *
   * @return bool|string
   */
  public function file( $file_name, $path = '', $root = false ) {

    // file list
    $directory = $this->directory( $path, $root );
    if( $file_name == '*' || $file_name{0} == '|' ) {

      $files = File::getList( $this->directory( $path, true ), false, $file_name == '*' ? false : preg_replace( '/(^\\||\\|$)/', '/', $file_name ) );
      foreach( $files as &$f ) $f = $directory . $f;

      return $files;

    } else return $directory . $file_name;
  }

  /**
   * Get the first exist library name or return false if none of them is exists
   *
   * @param string $class_name String with dot separated namespace ( exclude Package\Name\ ) or an array of this
   *                           strings ( return the first exist )
   *
   * @return string|false
   */
  public function library( $class_name ) {
    if( !is_array( $class_name ) ) $class_name = array( $class_name );

    $base = str_replace( '-', '\\', $this->id ) . '\\';
    foreach( $class_name as $name ) {

      $class = $base . str_replace( '.', '\\', $name );
      if( class_exists( $class, true ) ) return $class;
    }

    return false;
  }
  /**
   * Get extension library class instance with given param.
   *
   * @param string|array $class_name String with dot separated namespace ( exclude Package\Name\ ) or an array of this
   *                                 strings ( return the first exist )
   * @param mixed        $param      Array of params added to the contructor but NOT as a param list!
   *
   * @return mixed
   */
  public function instance( $class_name, $param = null ) {

    $class = $this->library( $class_name );
    if( !$class ) return null;
    else {

      $instance = isset( $param ) ? new $class( $param ) : new $class();
      return $instance;
    }
  }

  /**
   * Triggers an event of the extension and return the event object for the result
   *
   * @param string $event     The dot separated event name
   * @param array  $arguments Arguments passed to the event handlers
   *
   * @return Extension\Event
   */
  public function trigger( $event, $arguments = array() ) {

    $event = new Extension\Event( $this->id, $event, $arguments );
    return $event->execute();
  }
}