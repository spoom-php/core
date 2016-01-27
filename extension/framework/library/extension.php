<?php namespace Framework;

use Framework\Helper\File;
use Framework\Helper\Library;
use Framework\Helper\Log;

/**
 * One of the most important class in the framework. Handle all extension
 * stuffs eg: object creation, file getter, configuration and localization
 * managment and other
 *
 * @package Framework
 *
 * @property-read string                           $id            Unique name
 * @property-read Storage\File                     $manifest      The manifest storage
 * @property-read Extension\ConfigurationInterface $configuration The configuration storage object
 * @property-read Extension\LocalizationInterface  $localization  The localization storage object
 * @property-read Log                              $log           The default extension logger instance
 */
class Extension extends Library {

  /**
   * Extension instance cache
   *
   * @var array[string]Extension
   */
  private static $instance = [ ];

  /**
   * Exception code for invalid manifest data. This mostly indicates the extension package and name
   * missmatch in the manifest and the extension path. One data will be passed:
   *  - id [string]: Extension id
   */
  const EXCEPTION_CRITICAL_INVALID_MANIFEST = 'framework#4C';
  /**
   * Exception code for missing extension directory. One data will be passed:
   *  - id [string]: Extension id
   */
  const EXCEPTION_CRITICAL_MISSING_EXTENSION = 'framework#5C';
  /**
   * The configuration class definition invalid. Data:
   *  - extension [string]: The extension id
   *  - class [string]: The classname that is invalid (or null if not exist)
   *
   * @since 0.6.0
   */
  const EXCEPTION_INVALID_CONFIGURATION = 'framework#21C';
  /**
   * The localization class definition invalid. Data:
   *  - extension [string]: The extension id
   *  - class [string]: The classname that is invalid (or null if not exist)
   *
   * @since 0.6.0
   */
  const EXCEPTION_INVALID_LOCALIZATION = 'framework#22C';

  /**
   * Exception code for invalid extension id
   */
  const EXCEPTION_NOTICE_INVALID_ID = 'framework#6N';

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
   * Provide the manifest data access
   *
   * @var Storage\File
   */
  private $_manifest;
  /**
   * Extension directory from root without _PATH_BASE
   *
   * @var string
   */
  private $_directory = null;
  /**
   * Handle configuration files for the extension
   *
   * @var Extension\ConfigurationInterface
   */
  private $_configuration = null;
  /**
   * Handle localization files for the extension
   *
   * @var Extension\LocalizationInterface
   */
  private $_localization = null;
  /**
   * The logger instance for the extension (the log name is the extension id)
   *
   * @var Log
   */
  private $_log = null;

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

    // define the id
    if( !empty( $id ) ) $this->_id = $id;
    else {

      $class     = explode( '\\', mb_strtolower( get_class( $this ) ) );
      $this->_id = \Framework::search( $class );
    }

    if( !Extension\Helper::validate( $this->_id ) ) throw new Exception\Strict( self::EXCEPTION_NOTICE_INVALID_ID, [ 'id' => $this->_id ] );
    else {

      // define directory
      $directory = Extension\Helper::directory( $this->_id, false );
      if( !$directory ) throw new Exception\System( self::EXCEPTION_CRITICAL_MISSING_EXTENSION, [ 'id' => $this->_id ] );
      else {

        $this->_directory = $directory;
        $this->_manifest = new Storage\File( $this->_directory . 'manifest' );
      }
    }
  }

  /**
   * Clone the configuration and localization properties
   *
   * @since 0.6.0
   */
  public function __clone() {

    if( $this->_configuration ) $this->_configuration = clone $this->_configuration;
    if( $this->_localization ) $this->_localization = clone $this->_localization;
  }

  /**
   * Get language string from the extension language object. It's a proxy for Localization::getPattern() method
   *
   * @param string       $index
   * @param array|string $insertion
   * @param string       $default
   *
   * @return string
   */
  public function text( $index, $insertion = null, $default = '' ) {
    return $this->getLocalization()->getPattern( $index, $insertion, $default );
  }
  /**
   * Get configuration variable from extension configuration object. It's a proxy for Configuration::get() method
   *
   * @param string $index
   * @param mixed  $default
   *
   * @return mixed
   */
  public function option( $index, $default = null ) {
    return $this->getConfiguration()->get( $index, $default );
  }

  /**
   * Return an extension directory. The return path is relative to the extension directory by default
   * (it can be modified with the $root parameter)
   *
   * @param string      $path Path from extension root
   * @param bool|string $root Add _PATH_BASE constant or another ( or nothing )
   *
   * @return string
   */
  public function directory( $path = '', $root = false ) {

    $base = $root === true ? _PATH_BASE : ( is_string( $root ) ? $root : '' );
    return $base . rtrim( $this->_directory . ltrim( $path, '/' ), '/' ) . '/';
  }
  /**
   * Return an extension file ( or file list ). The return file path is relative to the extension directory by default
   * (it can be modified with the $root parameter)
   *
   * @param string  $file_name The file name ( use | and | for regexp file filter or * for directory listing )
   * @param string  $path      Path from the extension root
   * @param boolean $root Add _PATH_BASE constant or another prefix for the path ( or nothing )
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
    
    if( !is_array( $class_name ) ) $class_name = [ $class_name ];
    foreach( $class_name as $name ) {

      $class = \Framework::library( $this->_id . ':' . $name );
      if( class_exists( $class, true ) ) return $class;
    }

    // log: debug
    Request::getLog()->debug( 'Missing (class) library: {classnames}', [ 'classnames' => implode( ',', $class_name ) ], '\Framework\Extension' );

    return false;
  }
  /**
   * Create a new instance from extension library class with given param.
   *
   * @param string|array $class_name String with dot separated namespace ( exclude Package\Name\ ) or an array of this
   *                                 strings ( return the first exist )
   * @param mixed        $param      Array of params added to the contructor but NOT as a param list!
   *
   * @return mixed
   */
  public function create( $class_name, $param = null ) {

    $class = $this->library( $class_name );
    if( $class ) {

      $instance = isset( $param ) ? new $class( $param ) : new $class();
      return $instance;
    }

    // log: debug
    Request::getLog()->debug( 'Missing (class) instance: {classname}', [ 'classname' => $class_name ], '\Framework\Extension' );

    return null;
  }

  /**
   * Triggers an event of the extension and return the event object for the result
   *
   * @param string $event     The dot separated event name
   * @param array  $arguments Arguments passed to the event handlers
   *
   * @return Extension\Event
   */
  public function trigger( $event, $arguments = [ ] ) {

    $event = new Extension\Event( $this->_id, $event, $arguments );
    return $event->execute();
  }

  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getId() {
    return $this->_id;
  }
  /**
   * @since 0.6.0
   *
   * @return Storage\File
   */
  public function getManifest() {
    return $this->_manifest;
  }
  /**
   * @since 0.6.0
   *
   * @return Extension\ConfigurationInterface
   * @throws Exception\System
   */
  public function getConfiguration() {

    if( !$this->_configuration ) {

      $tmp   = $this->_manifest->getString( 'storage.configuration', 'framework:extension.configuration' );
      $class = \Framework::library( $tmp );
      if( $class && is_subclass_of( $class, '\Framework\Extension\ConfigurationInterface' ) ) $this->_configuration = new $class( $this );
      else throw new Exception\System( self::EXCEPTION_INVALID_CONFIGURATION, [
        'extension' => $this->_id,
        'class'     => \Framework::library( $tmp, false )
      ] );
    }

    return $this->_configuration;
  }
  /**
   * @since 0.6.0
   *
   * @return Extension\LocalizationInterface
   * @throws Exception\System
   */
  public function getLocalization() {

    if( !$this->_localization ) {

      $class = \Framework::library( $this->_manifest->getString( 'storage.localization', 'framework:extension.localization' ) );
      if( $class && is_subclass_of( $class, '\Framework\Extension\LocalizationInterface' ) ) $this->_localization = new $class( $this );
      else throw new Exception\System( self::EXCEPTION_INVALID_LOCALIZATION, [
        'extension' => $this->_id,
        'class'     => $class
      ] );
    }

    return $this->_localization;
  }
  /**
   * @since 0.6.0
   *
   * @return Log
   */
  public function getLog() {

    if( !$this->_log ) {
      $this->_log = Log::instance( $this->_id );
    }

    return $this->_log;
  }

  /**
   * Get an extension instance from the shared instance cache
   *
   * @param string $id The extension id
   *
   * @return static
   */
  public static function instance( $id ) {

    if( !isset( self::$instance[ $id ] ) ) {
      self::$instance[ $id ] = new static( $id );
    }

    return self::$instance[ $id ];
  }
}
