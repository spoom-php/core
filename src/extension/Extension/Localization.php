<?php namespace Spoom\Framework\Extension;

use Spoom\Framework\Application;
use Spoom\Framework\FileInterface;
use Spoom\Framework\Storage;
use Spoom\Framework\Converter;

/**
 * Interface LocalizationInterface
 * @package Framework\Extension
 *
 * @since   0.6.0
 */
interface LocalizationInterface extends Storage\PermanentInterface {

  /**
   * Get the current localization name
   *
   * @since 0.6.0
   *
   * @param bool $active Return the currenty active or the desired value
   *
   * @return null|string
   */
  public function getLocalization( bool $active = false ): ?string;
  /**
   * Set the current localization name
   *
   * @since 0.6.0
   *
   * @param string|null $value
   */
  public function setLocalization( ?string $value = null );
}

/**
 * Class Localization
 * @package Framework\Extension
 *
 * @property string $localization The current localization name
 */
class Localization extends Storage\File implements LocalizationInterface {

  /**
   * Desired localization
   *
   * @var string|null
   */
  private $_localization;
  /**
   * Last accessed localization
   *
   * @var string|null
   */
  private $active;

  /**
   * @var array
   */
  private $cache = [ '' ];

  /**
   * @param FileInterface $directory
   */
  public function __construct( FileInterface $directory ) {
    parent::__construct( $directory, [
      'json' => new Converter\Json( JSON_PRETTY_PRINT ),
      'ini'  => new Converter\Ini()
    ] );
  }

  /**
   * Check if the given localization name directory exists
   *
   * @param string $name The localization name to check
   *
   * @return bool
   */
  protected function validate( string $name ): bool {

    // search in the cache first
    if( in_array( $name, $this->cache ) ) return true;
    else {

      // check the environment existance
      $result = $this->getDirectory()->get( $name )->exist( [
        FileInterface::META_TYPE => FileInterface::TYPE_DIRECTORY
      ] );

      // populate the cache
      if( $result ) $this->cache[] = $result;
      return $result;
    }
  }
  //
  protected function searchFile( ?string $namespace, ?string $format = null ): FileInterface {

    $tmp = $this->getDirectory();
    try {

      // collect possible environment names
      $allow = [];
      if( $this->getLocalization() !== null ) $allow[] = $this->getLocalization();
      $allow[] = Application::instance()->getLocalization();
      $allow[] = '';

      //
      foreach( $allow as $localization ) {
        if( $this->validate( $localization ) ) {

          // clear meta/cache/storage when the localization has changed
          if( $this->active != $localization ) {

            $this[ '' ]            = [];
            $this->converter_cache = [];
          }

          $this->active = $localization;
          break;
        }
      }

      // change the directory temporary then search for the path
      if( !empty( $this->active ) ) {
        $this->setDirectory( $tmp->get( $this->active ) );
      }
      return parent::searchFile( $namespace, $format );

    } finally {
      $this->setDirectory( $tmp );
    }
  }

  //
  public function getLocalization( bool $active = false ):?string {
    return $active ? $this->cache : $this->_localization;
  }
  //
  public function setLocalization( ?string $value = null ) {
    $this->_localization = $value;
  }
}
