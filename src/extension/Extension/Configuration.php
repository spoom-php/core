<?php namespace Spoom\Framework\Extension;

use Spoom\Framework\Application;
use Spoom\Framework\FileInterface;
use Spoom\Framework\Storage;
use Spoom\Framework\Converter;
use Spoom\Framework\File;

/**
 * Interface ConfigurationInterface
 * @package Framework\Extension
 *
 * @since   0.6.0
 */
interface ConfigurationInterface extends Storage\PermanentInterface {

  /**
   * @param bool $active Return the currenty active or the desired value
   *
   * @return null|string
   */
  public function getEnvironment( $active = false );
  /**
   * @param string|null $value
   */
  public function setEnvironment( $value = null );
}

/**
 * Class Configuration
 * @package Framework\Extension
 *
 * @property      string|null $environment The actual environment's name
 */
class Configuration extends Storage\File implements ConfigurationInterface {

  /**
   * Desired environment
   *
   * @var string|null
   */
  private $_environment;
  /**
   * Last accessed environment
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
      new Converter\Json( JSON_PRETTY_PRINT ),
      new Converter\Xml(),
      new Converter\Ini()
    ] );
  }

  /**
   * Check if the given environment' directory exists
   *
   * @param string $name The environment name
   *
   * @return bool
   */
  protected function validate( $name ) {

    // search in the cache first
    if( in_array( $name, $this->cache ) ) return true;
    else {

      // check the environment existance
      $result = $this->getDirectory()->get( $name )->exist( [
        File\System::META_TYPE => File\System::TYPE_DIRECTORY
      ] );

      // populate the cache
      if( $result ) $this->cache[] = $result;
      return $result;
    }
  }
  //
  protected function searchFile( $namespace, $format = null ) {

    $tmp = $this->getDirectory();
    try {

      // collect possible environment names
      $allow = [];
      if( $this->_environment !== null ) $allow[] = $this->_environment;
      $allow[] = Application::instance()->getEnvironment();
      $allow[] = '';

      //
      foreach( $allow as $environment ) {
        if( $this->validate( $environment ) ) {

          // clear meta/cache/storage when the environment has changed
          if( $this->active != $environment ) {

            $this->_source         = [];
            $this->converter_cache = [];
            $this->clean();
          }

          $this->active = $environment;
          break;
        }
      }

      // change the directory temporary then search for the path
      if( !empty( $this->active ) ) {
        $this->setDirectory( $tmp->get( $this->active ) );
      }

      // search like normal
      return parent::searchFile( $namespace, $format );

    } finally {
      $this->setDirectory( $tmp );
    }
  }

  //
  public function getEnvironment( $active = false ) {
    return $active ? $this->cache : $this->_environment;
  }
  //
  public function setEnvironment( $value = null ) {
    $this->_environment = $value;
  }
}
