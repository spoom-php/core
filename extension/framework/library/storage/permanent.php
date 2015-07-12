<?php namespace Framework\Storage;

use Framework\Exception;
use Framework\Extension;
use Framework\Helper;
use Framework\Helper\Enumerable;
use Framework\Storage;
use Framework\StorageInterface;

/**
 * Interface PermanentInterface
 * @package Framework\Storage
 */
interface PermanentInterface extends StorageInterface {

  /**
   * Save the namespace's actual storage data in the given (or the default) format
   *
   * @param string|null $format    The format that the converter instance will recognise
   * @param string|null $namespace The namespace to save
   *
   * @return $this
   */
  public function save( $format = null, $namespace = null );
  /**
   * Load the given namespace into the storage
   *
   * @param string|null $namespace The namespace to load
   *
   * @return $this
   */
  public function load( $namespace = null );
  /**
   * Remove the namespace data from the storage and the permanent storage
   *
   * @param string|null $namespace
   *
   * @return $this
   */
  public function remove( $namespace = null );

  /**
   * Get the latest exception object
   *
   * @return Exception|null
   */
  public function getException();
  /**
   * Get the converter object that parse and build the input and output object/string
   *
   * @return Helper\Converter
   */
  public function getConverter();
  /**
   * Autoload the namespaces or not
   *
   * @return boolean
   */
  public function isAuto();
  /**
   * Set new value to autoload the namespaces or not
   *
   * @param boolean $value
   */
  public function setAuto( $value );
  /**
   * Get the default converter format
   *
   * @return string
   */
  public function getFormat();
  /**
   * Set the default converter format
   *
   * @param string $value
   */
  public function setFormat( $value );
}
/**
 * Class Permanent
 * @package Framework\Storage
 *
 * TODO add static cache for the load mechanism to optimize the process
 *
 * @property-read Exception|null   $exception The latest exception object
 * @property-read Helper\Converter $converter The converter object that parse and build the input and output object/string
 * @property      bool             $auto      Autoload the namespaces or not
 * @property      string           $format    The default format for saving
 */
abstract class Permanent extends Storage implements PermanentInterface {

  /**
   * There is no meta for a namespace to able to read and process the data. Data:
   *  - namespace [string]: The namespace that has no meta
   */
  const EXCEPTION_MISSING_META = 'framework#23W';

  /**
   * Store metadata for loaded namespaces
   *
   * @var Helper\ConverterMeta[string]
   */
  protected $meta = [ ];
  /**
   * Store the latest exception object
   *
   * @var Exception|null
   */
  protected $_exception;
  /**
   * The converter object that parse and build the input and output object/string
   *
   * @var Helper\Converter
   */
  protected $_converter;
  /**
   * Autoload the namespaces or not
   *
   * @var bool
   */
  protected $_auto = true;
  /**
   * The default format for saving
   *
   * @var string
   */
  protected $_format = 'json';

  /**
   * @param mixed|null            $data      The initial data
   * @param string|null           $namespace The default namespace
   * @param int                   $caching   The caching mechanism
   * @param Helper\Converter|null $converter Custom converter or use the default (null)
   */
  public function __construct( $data = null, $namespace = null, $caching = self::CACHE_SIMPLE, Helper\Converter $converter = null ) {
    parent::__construct( $data, $namespace, $caching );

    $this->_converter = $converter ?: new Helper\Converter();
  }

  /**
   * Clone the converter and all of the stored meta
   */
  public function __clone() {
    parent::__clone();

    $this->_converter = clone $this->_converter;
    $this->meta       = Enumerable::copy( $this->meta );
  }

  /**
   * Save the namespace's actual storage data in the given (or the default) format
   *
   * @param string|null $format    The format that the converter instance will recognise
   * @param string|null $namespace The namespace to save
   *
   * @return $this
   */
  public function save( $format = null, $namespace = null ) {
    $this->reset();

    try {

      if( !empty( $format ) ) $meta = new Helper\ConverterMeta( $format );
      else $meta = isset( $this->meta[ $namespace ] ) ? $this->meta[ $namespace ] : new Helper\ConverterMeta( $this->_format );

      $index   = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
      $content = $this->_converter->serialize( $this->getObject( $index ), $meta );

      $this->meta[ $namespace ] = $meta;
      $this->write( $content, $namespace );

    } catch( \Exception $e ) {
      $this->_exception = Exception\Helper::wrap( $e )->log();
    }

    return $this;
  }
  /**
   * Load the given namespace into the storage
   *
   * @param string|null $namespace The namespace to load
   *
   * @return $this
   */
  public function load( $namespace = null ) {
    $this->reset();

    try {

      $content = $this->read( $namespace );
      if( empty( $this->meta[ $namespace ] ) || !( $this->meta[ $namespace ] instanceof Helper\ConverterMeta ) ) {
        throw new Exception\Strict( self::EXCEPTION_MISSING_META, [ 'namespace' => $namespace ] );
      } else {

        $index = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
        $this->set( $index, $this->_converter->unserialize( $content, $this->meta[ $namespace ] ) );
      }

    } catch( \Exception $e ) {
      $this->_exception = Exception\Helper::wrap( $e )->log();
    }

    return $this;
  }
  /**
   * Remove the namespace data from the storage and the permanent storage
   *
   * @param string|null $namespace
   *
   * @return $this
   */
  public function remove( $namespace = null ) {
    $this->reset();

    try {

      $this->destroy( $namespace );

      $index = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
      $this->clear( $index );

      unset( $this->meta[ $namespace ] );

    } catch( \Exception $e ) {
      $this->_exception = Exception\Helper::wrap( $e )->log();
    }

    return $this;
  }

  /**
   * Reset the exception state
   */
  protected function reset() {
    $this->_exception = null;
  }
  /**
   * @inheritdoc. Setup the autoloader for namespaces
   */
  protected function search( $index, $build = false, $is_read = true ) {

    // try to load the storage data if there is no already
    if( $this->isAuto() && empty( $this->meta[ $index->namespace ] ) ) {
      $this->load( $index->namespace );
    }

    // delegate problem to the parent
    return parent::search( $index, $build, $is_read );
  }

  /**
   * @return Exception|null
   */
  public function getException() {
    return $this->_exception;
  }
  /**
   * @return Helper\Converter
   */
  public function getConverter() {
    return $this->_converter;
  }
  /**
   * @return boolean
   */
  public function isAuto() {
    return $this->_auto;
  }
  /**
   * @param boolean $value
   */
  public function setAuto( $value ) {
    $this->_auto = (bool) $value;
  }
  /**
   * @return string
   */
  public function getFormat() {
    return $this->_format;
  }
  /**
   * @param string $value
   */
  public function setFormat( $value ) {
    $this->_format = (string) $value;
  }

  /**
   * Write out the string content to the permantent storage. The meta for the namespace IS available through the meta property
   *
   * @param string      $content   The content to write out
   * @param string|null $namespace The namespace of the content
   */
  abstract protected function write( $content, $namespace = null );
  /**
   * Read a namespace data from the permanent storage. The meta for the namespace MUST exist after the read in the meta property
   *
   * @param string|null $namespace The namespace
   *
   * @return string|null
   */
  abstract protected function read( $namespace = null );
  /**
   * Destroy a namespace's permanent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string|null $namespace The namespace
   */
  abstract protected function destroy( $namespace = null );
}
