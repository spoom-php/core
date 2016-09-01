<?php namespace Framework\Helper;

use Framework\Exception;

/**
 * Interface ConverterInterface
 *
 * TODO handle errors (failable interface?!)
 *
 * @package Framework\Helper
 */
interface ConverterInterface extends LibraryInterface, FailableInterface {

  /**
   * Try to set a wrong type of meta class
   *
   * @param string $class The necessary meta class name
   * @param mixed  $value The wrong meta
   */
  const EXCEPTION_INVALID_META = 'framework#28E';
  /**
   * Failed serialization
   *
   * @param ConverterInterface $instance The converter
   * @param mixed              $content  The content to serialize
   * @param mixed              $error    The error description, if any
   */
  const EXCEPTION_FAIL_SERIALIZE = 'framework#29E';
  /**
   * Failed de-serialization
   *
   * @param ConverterInterface $instance The converter
   * @param string             $content  The content to unserialize
   * @param mixed              $error    The error description, if any
   */
  const EXCEPTION_FAIL_UNSERIALIZE = 'framework#30E';

  /**
   * Serialize the content to a formatted (based on the meta property) string
   *
   * @param mixed $content The content to serialize
   *
   * @return string|null
   */
  public function serialize( $content );
  /**
   * Unserialize string into a php value
   *
   * @param string $content The content to unserialize
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
   * @throws Exception\Strict
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
 */
class Converter extends Library {

  /**
   * Try to add a non-ConverterInterface instance
   */
  const EXCEPTION_INVALID_CONVERTER = 'framework#31E';

  /**
   * Available converters
   *
   * @var ConverterInterface[]
   */
  private $_list = [];

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
  function __clone() {
    $this->_list = Enumerable::copy( $this->_list );
  }

  /**
   * Add a new converter to the collection
   *
   * @param ConverterInterface $converter
   * @param bool               $overwrite Overwrite the exists converter with the same name
   *
   * @return $this
   * @throws Exception\Strict
   */
  public function add( $converter, $overwrite = true ) {

    if( !( $converter instanceof ConverterInterface ) ) throw new Exception\Strict( static::EXCEPTION_INVALID_CONVERTER );
    else {

      $format = $converter->getFormat();
      $tmp    = isset( $this->_list[ $format ] ) ? $this->_list[ $format ] : null;
      if( empty( $tmp ) || $overwrite ) $this->_list[ $format ] = $converter;

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

    if( empty( $format ) ) $this->_list = [];
    else unset( $this->_list[ (string) $format ] );
  }
  /**
   * Get a converter (or all converter) from the collection, based on the format
   *
   * @param string|null $format The name of the converter, or empty for all
   *
   * @return ConverterInterface|ConverterInterface[]|null
   */
  public function get( $format = null ) {
    return empty( $format ) ? $this->_list : ( isset( $this->_list[ $format ] ) ? $this->_list[ $format ] : null );
  }
}
