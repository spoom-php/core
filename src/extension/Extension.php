<?php namespace Spoom\Core;

use Spoom\Core\Helper;
use Spoom\Core\Storage\PersistentInterface;

//
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
   * @param string $path
   *
   * @return FileInterface
   */
  public function getFile( string $path = '' ): FileInterface;
  /**
   * @since 0.6.0
   *
   * @return string
   */
  public function getId(): string;
  /**
   * @since 0.6.0
   *
   * @return PersistentInterface|null
   */
  public function getConfiguration(): ?PersistentInterface;
  /**
   * @since 0.6.0
   *
   * @return PersistentInterface|null
   */
  public function getLocalization(): ?PersistentInterface;
  /**
   * @since 0.6.0
   *
   * @return LoggerInterface
   */
  public function getLogger(): LoggerInterface;
}

/**
 * @property-read FileInterface            $file          Root directory of the extension
 * @property-read string                   $id            Unique name
 * @property-read PersistentInterface|null $configuration
 * @property-read PersistentInterface|null $localization
 * @property-read LoggerInterface          $logger
 */
class Extension implements ExtensionInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  const ID   = 'spoom-core';
  const ROOT = __DIR__ . '/../';

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
  protected $_file;
  /**
   * Handle configuration files for the extension
   *
   * @var PersistentInterface|null
   */
  protected $_configuration;
  /**
   * Handle localization files for the extension
   *
   * @var PersistentInterface|null
   */
  protected $_localization;
  /**
   * @var LoggerInterface|null
   */
  protected $_logger;

  /**
   *
   */
  protected function __construct() {
    $this->_file    = new File( static::ROOT );
  }

  //
  public function text( string $index, $insertion = null, string $default = '' ): string {
    return $this->getLocalization() ? $this->getLocalization()->getPattern( $index, $insertion, $default ) : $default;
  }
  //
  public function option( string $index, $default = null ) {
    return $this->getConfiguration() ? $this->getConfiguration()->get( $index, $default ) : $default;
  }

  //
  public function file( string $path = '', ?string $pattern = null ) {

    $file = $this->getFile( $path );
    return empty( $pattern ) ? $file : $file->search( $pattern === '*' ? null : $pattern );
  }

  //
  public function getFile( string $path = '' ): FileInterface {
    return $this->_file->get( $path );
  }
  //
  public function getId(): string {
    return static::ID;
  }
  //
  public function getConfiguration(): ?PersistentInterface {
    return $this->_configuration;
  }
  //
  public function getLocalization(): ?PersistentInterface {
    return $this->_localization;
  }
  //
  public function getLogger(): LoggerInterface {
    return $this->_logger;
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
