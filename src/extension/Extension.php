<?php namespace Spoom\Framework;

use Spoom\Framework\Helper;

/**
 * Interface ExtensionInterface
 * @package Spoom\Framework
 *
 * @property-read File\SystemInterface             $filesystem Filesystem, relative to the extension's root
 * @property-read string                           $id         Unique name
 * @property-read Extension\ConfigurationInterface $configuration
 * @property-read Extension\LocalizationInterface  $localization
 * @property-read LogInterface                     $log
 * @property-read Event\StorageInterface           $event_storage
 */
interface ExtensionInterface {

  /**
   * Get language string from the extension language object
   *
   * It's a proxy for Localization::getPattern() method
   *
   * @param string       $index
   * @param array|string $insertion
   * @param string       $default
   *
   * @return string
   */
  public function text( string $index, $insertion = null, string $default = '' ): string;
  /**
   * Get configuration variable from extension configuration object
   *
   * It's a proxy for Configuration::get() method
   *
   * @param string $index
   * @param mixed  $default
   *
   * @return mixed
   */
  public function option( string $index, $default = null );

  /**
   * Get or search file(s) in the extension's directory
   *
   * @param string      $path    Sub-path, relative from the extension directory
   * @param string|null $pattern Pattern for file listing. Accept '*' wildcard
   *
   * @return FileInterface|FileInterface[]
   */
  public function file( string $path = '', ?string $pattern = null );

  /**
   * Triggers an event with the extension's storage
   *
   * ..and return the event as the result
   *
   * @param string $name Event (name) to trigger
   * @param array  $data Default event data
   *
   * @return EventInterface
   */
  public function trigger( string $name, $data = [] ): EventInterface;

  /**
   *
   * @since ???
   *
   * @return File\SystemInterface
   */
  public function getFilesystem(): File\SystemInterface;
  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getId(): string;
  /**
   * @since 0.6.0
   *
   * @return Extension\ConfigurationInterface
   */
  public function getConfiguration(): Extension\ConfigurationInterface;
  /**
   * @since 0.6.0
   *
   * @return Extension\LocalizationInterface
   */
  public function getLocalization(): Extension\LocalizationInterface;
  /**
   * @since 0.6.0
   *
   * @return LogInterface
   */
  public function getLog(): LogInterface;
  /**
   * @since ??
   *
   * @return Event\StorageInterface
   */
  public function getEventStorage(): Event\StorageInterface;
}

/**
 * Class Extension
 * @package Spoom\Framework
 */
class Extension implements ExtensionInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  const ID = 'spoom-framework';

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
   * Extension instance cache
   *
   * @var array[string]Extension
   */
  private static $instance = [];

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
  /**
   * @var LogInterface
   */
  private $_log;

  /**
   *
   */
  protected function __construct() {

    $this->_filesystem = new File\System( dirname( __DIR__ ) );

    //
    $directory            = Application::instance()->getPublicFile( $this->getId() );
    $this->_configuration = new Extension\Configuration( $directory->get( static::DIRECTORY_CONFIGURATION ) );
    $this->_localization  = new Extension\Localization( $directory->get( static::DIRECTORY_LOCALIZATION ) );
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

  //
  public function text( string $index, $insertion = null, string $default = '' ): string {
    return $this->getLocalization()->getPattern( $index, $insertion, $default );
  }
  //
  public function option( string $index, $default = null ) {
    return $this->getConfiguration()->get( $index, $default );
  }

  //
  public function file( string $path = '', ?string $pattern = null ) {

    $file = $this->getFilesystem()->get( $path );
    return empty( $pattern ) ? $file : $file->search( $pattern === '*' ? null : $pattern );
  }

  //
  public function trigger( string $name, $data = [] ): EventInterface {
    return $this->getEventStorage()->trigger( new Event( $name, $data ) );
  }

  //
  public function getFilesystem(): File\SystemInterface {

    if( empty( $this->_filesystem ) ) {

    }

    return $this->_filesystem;
  }
  //
  public function getId(): string {
    return static::ID;
  }
  //
  public function getConfiguration(): Extension\ConfigurationInterface {
    return $this->_configuration;
  }
  //
  public function getLocalization(): Extension\LocalizationInterface {
    return $this->_localization;
  }
  //
  public function getLog(): LogInterface {
    return $this->_log;
  }
  //
  public function getEventStorage(): Event\StorageInterface {
    return $this->_event_storage;
  }

  /**
   * Get an extension instance from the shared instance cache
   *
   * @return static
   */
  public static function instance() {

    $id = static::ID;
    return self::$instance[ $id ] ?? ( self::$instance[ $id ] = new static() );
  }
}