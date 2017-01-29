<?php namespace Framework;

use Framework\Helper;

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
 * @property-read LogInterface                     $log           The default extension logger instance
 * @property-read Event\StorageInterface           $event         Event storage
 */
class Extension implements Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Extension instance cache
   *
   * @var array[string]Extension
   */
  private static $instance = [];

  /**
   * Exception code for missing extension directory. One data will be passed:
   *  - id [string]: Extension id
   */
  const EXCEPTION_MISSING_EXTENSION = 'framework#5C';
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
  const EXCEPTION_INVALID_ID = 'framework#6W';

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
   * Extension directory
   *
   * @var FileInterface
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
   * Handle event triggers for the extension
   *
   * @var Event\StorageInterface
   */
  private $_event = null;

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

    if( !Extension\Helper::validate( $this->_id ) ) throw new Exception\Strict( self::EXCEPTION_INVALID_ID, [ 'id' => $this->_id ] );
    else {

      // define directory
      $directory = Extension\Helper::directory( $this->_id, false );
      if( !$directory ) throw new Exception\System( self::EXCEPTION_MISSING_EXTENSION, [ 'id' => $this->_id ] );
      else {

        $this->_directory = $directory;
        $this->_manifest = new Storage\File( $this->_directory, [
          new Converter\Json( JSON_PRETTY_PRINT )
        ], 'manifest' );
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
   * Get or search file(s) in the extension's directory
   *
   * @param string      $path    Sub-path, relative from the extension directory
   * @param string|null $pattern Pattern for file listing. Accept '*' wildcard
   *
   * @return FileInterface|FileInterface[]
   */
  public function file( $path = '', $pattern = null ) {

    $directory = $this->getDirectory()->get( $path );
    return empty( $pattern ) ? $directory : $directory->search( $pattern === '*' ? null : $pattern );
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
      if( $class ) return $class;
    }

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

    return null;
  }

  /**
   * Triggers an event of the extension and return the event as the result
   *
   * @param string $name Event (name) to trigger
   * @param array  $data Default event data
   *
   * @return EventInterface
   */
  public function trigger( $name, $data = [] ) {
    return $this->getEvent()->trigger( new Event( $name, $data ) );
  }

  /**
   *
   * @since ???
   *
   * @return FileInterface
   */
  public function getDirectory() {
    return $this->_directory;
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
   * @return LogInterface
   */
  public function getLog() {
    return Log::instance( $this->_id );
  }
  /**
   * @since ??
   *
   * @return Event\StorageInterface
   */
  public function getEvent() {

    if( !$this->_event ) {
      $this->_event = new Event\Storage( $this->_id );
    }

    return $this->_event;
  }

  /**
   * Get an extension instance from the shared instance cache
   *
   * @param string $id The extension id
   *
   * @return static
   */
  public static function instance( $id ) {
    return isset( self::$instance[ $id ] ) ? self::$instance[ $id ] : ( self::$instance[ $id ] = new static( $id ) );
  }
}
