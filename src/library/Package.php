<?php namespace Spoom\Core;

use Spoom\Core\Helper;
use Spoom\Core\Storage\PersistentInterface;

//
interface PackageInterface {

  /**
   * Get language string from the package language object
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
   * Get configuration variable from package configuration object
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
   * @param string $path
   *
   * @return FileInterface
   */
  public function file( string $path = '' ): FileInterface;

  /**
   * @return string
   */
  public function getId(): string;
  /**
   * @return PersistentInterface|null
   */
  public function getConfiguration(): ?PersistentInterface;
  /**
   * @return PersistentInterface|null
   */
  public function getLocalization(): ?PersistentInterface;
}

/**
 * @property-read string                   $id            Unique name
 * @property-read PersistentInterface|null $configuration
 * @property-read PersistentInterface|null $localization
 */
class Package implements PackageInterface, Helper\AccessableInterface {
  use Helper\Accessable;

  const ID   = 'spoom-core';
  const ROOT = __DIR__ . '/../';

  /**
   * Default directory for localization files
   */
  const DIRECTORY_LOCALIZATION = 'localization/';
  /**
   * Default directory for configuration files
   */
  const DIRECTORY_CONFIGURATION = 'configuration/';

  /**
   * Package instance cache
   *
   * @var array<string,Package>
   */
  private static $instance = [];

  /**
   * Package directory
   *
   * @var FileInterface
   */
  protected $_file;
  /**
   * Handle configuration files for the package
   *
   * @var PersistentInterface|null
   */
  protected $_configuration;
  /**
   * Handle localization files for the package
   *
   * @var PersistentInterface|null
   */
  protected $_localization;

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
  public function file( string $path = '' ): FileInterface {
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

  /**
   * Get a package instance from the shared instance cache
   *
   * @return static
   */
  public static function instance() {

    $id = static::ID;
    return self::$instance[ $id ] ?? ( self::$instance[ $id ] = new static() );
  }
}
