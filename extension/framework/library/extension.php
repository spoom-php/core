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
 * @property-read Storage\PermanentInterface       $manifest      The manifest storage
 * @property-read Extension\ConfigurationInterface $configuration The configuration storage object
 * @property-read Extension\LocalizationInterface  $localization  The localization storage object
 * @property-read LogInterface                     $log           The default extension logger instance
 * @property-read Event\StorageInterface           $event         Event storage
 */
class Extension implements Helper\AccessableInterface {
  use Helper\Accessable;

  const ID = 'framework';

  /**
   * Extension instance cache
   *
   * @var array[string]Extension
   */
  private static $instance = [];

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
  private $_filesystem = null;
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

  protected function __construct() {

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

    $file = $this->getFilesystem()->get( $path );
    return empty( $pattern ) ? $file : $file->search( $pattern === '*' ? null : $pattern );
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
   * @return File\SystemInterface
   */
  public function getFilesystem() {

    if( empty( $this->_filesystem ) ) {
      $this->_filesystem = new File\System( dirname( __DIR__ ) );
    }

    return $this->_filesystem;
  }
  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getId() {
    return static::ID;
  }
  /**
   * @since 0.6.0
   *
   * @return Storage\PermanentInterface
   */
  public function getManifest() {

    if( empty( $this->_manifest ) ) {
      $this->_manifest = new Storage\File( $this->getFilesystem()->get( '' ), [
        new Converter\Json( JSON_PRETTY_PRINT )
      ], 'composer' );
    }

    return $this->_manifest;
  }
  /**
   * @since 0.6.0
   *
   * @return Extension\ConfigurationInterface
   */
  public function getConfiguration() {

    if( empty( $this->_configuration ) ) {
      $this->_configuration = new Extension\Configuration( $this );
    }

    return $this->_configuration;
  }
  /**
   * @since 0.6.0
   *
   * @return Extension\LocalizationInterface
   */
  public function getLocalization() {

    if( empty( $this->_localization ) ) {
      $this->_localization = new Extension\Localization( $this );
    }

    return $this->_localization;
  }
  /**
   * @since 0.6.0
   *
   * @return LogInterface
   */
  public function getLog() {
    return Log::instance( $this->getId() );
  }
  /**
   * @since ??
   *
   * @return Event\StorageInterface
   */
  public function getEvent() {

    if( !$this->_event ) {
      $this->_event = new Event\Storage( $this->getId() );
    }

    return $this->_event;
  }

  /**
   * Get an extension instance from the shared instance cache
   *
   * @return static
   */
  public static function instance() {

    $id = static::ID;
    return isset( self::$instance[ $id ] ) ? self::$instance[ $id ] : ( self::$instance[ $id ] = new static() );
  }
}
