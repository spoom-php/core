<?php namespace Spoom\Framework;

use Spoom\Framework\Exception;
use Spoom\Framework\Helper;

/**
 * Interface ConverterInterface
 * @package Framework\Helper
 */
interface ConverterInterface extends Helper\FailableInterface {

  /**
   * Serialize the content to a formatted (based on the meta property) string
   *
   * @param mixed    $content The content to serialize
   * @param resource $stream  Optional output stream
   *
   * @return string|null
   */
  public function serialize( $content, $stream = null );
  /**
   * Unserialize string into a php value
   *
   * @param string|resource $content The content (can be a stream) to unserialize
   *
   * @return mixed
   */
  public function unserialize( $content );

  /**
   * @return mixed
   */
  public function getMeta();
  /**
   * @param mixed $value
   *
   * @return $this
   * @throws \InvalidArgumentException Try to set a wrong Meta subclass
   */
  public function setMeta( $value );

  /**
   * @return string The name of the format that the converter use
   */
  public function getFormat();
  /**
   * @return string The unique name of the converter
   */
  public function getName();
}
/**
 * Class Converter
 * @package Framework\Helper
 *
 * @property-read array $map Custom format map to converter formats
 */
class Converter implements Helper\AccessableInterface {
  use Helper\Accessable;

  /**
   * Available converters
   *
   * @var ConverterInterface[]
   */
  private $list = [];
  /**
   * Map custom format names (key) to converter formats (value)
   *
   * @var array
   */
  private $_map = [];

  /**
   * @param ConverterInterface[] $list
   */
  public function __construct( $list = [] ) {
    foreach( $list as $converter ) {
      $this->add( $converter );
    }
  }
  /**
   *
   */
  public function __clone() {
    $this->list = Helper\Enumerable::copy( $this->list );
  }

  /**
   * Add custom format and map it to an original converter format. This will override the `->get()` parameter
   * if a map exists
   *
   * @param string|array $custom An array of custom definitions, or custom defined format name to map with
   * @param string|null  $format Original converter format name
   *
   * @return static
   */
  public function setMap( $custom, $format = null ) {
    if( is_array( $custom ) ) $this->_map = $custom;
    else $this->_map[ $custom ] = $format;

    return $this;
  }
  /**
   * Remove a previously defined mapping
   *
   * @param string $custom
   *
   * @return static
   */
  public function removeMap( $custom = null ) {
    if( $custom === null ) $this->_map = [];
    else unset( $this->_map[ $custom ] );

    return $this;
  }
  /**
   * Get a custom format mapped format name, or all maps
   *
   * @param string|null $custom
   *
   * @return array|string|null
   */
  public function getMap( $custom = null ) {
    if( $custom === null ) return $this->_map;
    else return isset( $this->_map[ $custom ] ) ? $this->_map[ $custom ] : null;
  }

  /**
   * Add a new converter to the collection
   *
   * @param ConverterInterface $converter
   * @param bool               $overwrite Overwrite the exists converter with the same name
   *
   * @return $this
   * @throws \InvalidArgumentException Try to add a non ConverterInterface class
   */
  public function add( $converter, $overwrite = true ) {

    if( !( $converter instanceof ConverterInterface ) ) throw new \InvalidArgumentException( 'Converter must implement the ' . ConverterInterface::class );
    else {

      $format = $converter->getFormat();
      $tmp    = isset( $this->list[ $format ] ) ? $this->list[ $format ] : null;
      if( empty( $tmp ) || $overwrite ) $this->list[ $format ] = $converter;

      return $this;
    }
  }
  /**
   * Remove a converter (or all converter) from the list, based on the format
   *
   * TODO remove by converter instance or name
   *
   * @param string|null $format The format of the converter, or null for remove all
   */
  public function remove( $format = null ) {

    if( empty( $format ) ) $this->list = [];
    else unset( $this->list[ (string) $format ] );
  }
  /**
   * Get a converter (or all converter) from the collection, based on the format
   *
   * @param string|null $format The name of the converter, or empty for all
   *
   * @return ConverterInterface|ConverterInterface[]|null
   */
  public function get( $format = null ) {
    if( $format === null ) return $this->list;
    else {

      $tmp = $this->getMap( $format );
      if( $tmp ) $format = $tmp;

      return ( isset( $this->list[ $format ] ) ? $this->list[ $format ] : null );
    }
  }
}

/**
 * Failed (de-)serialization
 *
 * @package Framework
 */
class ConverterExceptionFail extends Exception\Logic {

  const ID = '29#framework';

  /**
   * @param ConverterInterface $instance
   * @param mixed              $content The content to (un)serialize
   * @param mixed              $error
   */
  public function __construct( ConverterInterface $instance, $content, $error = null ) {

    $data = [ 'instance' => $instance, 'content' => $content, 'error' => $error ];
    parent::__construct( '(Un)serialization failed, due to an error', static::ID, $data );
  }
}
