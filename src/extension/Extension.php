<?php namespace Spoom\Framework;

use Spoom\Framework\Helper;

/**
 * Interface ExtensionInterface
 * @package Spoom\Framework
 */
interface ExtensionInterface {

  /**
   * Get language string from the extension language object. It's a proxy for Localization::getPattern() method
   *
   * @param string       $index
   * @param array|string $insertion
   * @param string       $default
   *
   * @return string
   */
  public function text( $index, $insertion = null, $default = '' );
  /**
   * Get configuration variable from extension configuration object. It's a proxy for Configuration::get() method
   *
   * @param string $index
   * @param mixed  $default
   *
   * @return mixed
   */
  public function option( $index, $default = null );

  /**
   * Get or search file(s) in the extension's directory
   *
   * @param string      $path    Sub-path, relative from the extension directory
   * @param string|null $pattern Pattern for file listing. Accept '*' wildcard
   *
   * @return FileInterface|FileInterface[]
   */
  public function file( $path = '', $pattern = null );

  /**
   * Triggers an event of the extension and return the event as the result
   *
   * @param string $name Event (name) to trigger
   * @param array  $data Default event data
   *
   * @return EventInterface
   */
  public function trigger( $name, $data = [] );

  /**
   *
   * @since ???
   *
   * @return File\SystemInterface
   */
  public function getFilesystem();
  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getId();
  /**
   * @since 0.6.0
   *
   * @return Extension\ConfigurationInterface
   */
  public function getConfiguration();
  /**
   * @since 0.6.0
   *
   * @return Extension\LocalizationInterface
   */
  public function getLocalization();
  /**
   * @since 0.6.0
   *
   * @return LogInterface
   */
  public function getLog();
  /**
   * @since ??
   *
   * @return Event\StorageInterface
   */
  public function getEventStorage();
}

/**@package Framework
 *
 * @property-read string                           $id            Unique name
 * @property-read Extension\ConfigurationInterface $configuration The configuration storage object
 * @property-read Extension\LocalizationInterface  $localization  The localization storage object
 * @property-read LogInterface                     $log           The default extension logger instance
 * @property-read Event\StorageInterface           $event_storage Event storage
 * @property-read File\SystemInterface             $filesystem    Filesystem, relative to the extension's root
 */
class Extension implements ExtensionInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  const ID = 'spoom-framework';

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
   * Extension directory
   *
   * @var FileInterface
   */
  private $_filesystem;
  /**
   * Handle configuration files for the extension
   *
   * @var Extension\ConfigurationInterface
   */
  private $_configuration;
  /**
   * Handle localization files for the extension
   *
   * @var Extension\LocalizationInterface
   */
  private $_localization;
  /**
   * Handle event triggers for the extension
   *
   * @var Event\StorageInterface
   */
  private $_event_storage;
  private $_log;

  protected function __construct() {

    $this->_filesystem    = new File\System( dirname( __DIR__ ) );
    $this->_configuration = new Extension\Configuration( $this->file( static::DIRECTORY_CONFIGURATION ) );
    $this->_localization  = new Extension\Localization( $this->file( static::DIRECTORY_LOCALIZATION ) );
    $this->_event_storage = new Event\Storage( $this->getId() );

    $this->_log = clone Application::instance()->getLog();
    $this->_log->setChannel( $this->getId() );
  }

  /**
   * Clone the configuration and localization properties
   *
   * @since 0.6.0
   */
  public function __clone() {

    $this->_configuration = clone $this->_configuration;
    $this->_localization  = clone $this->_localization;
    $this->_log           = clone $this->_log;
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
    return $this->getEventStorage()->trigger( new Event( $name, $data ) );
  }

  /**
   *
   * @since ???
   *
   * @return File\SystemInterface
   */
  public function getFilesystem() {

    if( empty( $this->_filesystem ) ) {

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
   * @return Extension\ConfigurationInterface
   */
  public function getConfiguration() {
    return $this->_configuration;
  }
  /**
   * @since 0.6.0
   *
   * @return Extension\LocalizationInterface
   */
  public function getLocalization() {
    return $this->_localization;
  }
  /**
   * @since 0.6.0
   *
   * @return LogInterface
   */
  public function getLog() {
    return $this->_log;
  }
  /**
   * @since ??
   *
   * @return Event\StorageInterface
   */
  public function getEventStorage() {
    return $this->_event_storage;
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
