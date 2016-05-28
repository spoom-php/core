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
 *
 * @since   0.6.0
 */
interface PermanentInterface extends StorageInterface, Helper\FailableInterface {

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
 * @since   0.6.0
 *
 * TODO add static cache for the load mechanism to optimize the process
 * TODO add write- and readable feature
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
   * Triggered before the namespace saving. The save can be prevented. Arguments:
   * - instance [Permanent]: The storage instance
   * - namespace [string|null]: The namespace to save
   * - &meta [ConverterMeta]: The content meta data
   * - &content [mixed]: The content to save
   */
  const EVENT_SAVE = 'storage.permanent.save';
  /**
   * Triggered before the namespace loading. The load can be prevented. Arguments:
   * - instance [Permanent]: The storage instance
   * - namespace [string|null]: The namespace to load
   * - &meta [ConverterMeta|null]: The content output meta data
   */
  const EVENT_LOAD = 'storage.permanent.load';
  /**
   * Triggered before the namespace removing. The remove can be prevented. Arguments:
   * - instance [Permanent]: The storage instance
   * - namespace [string|null]: The namespace to remove
   * - &meta [ConverterMeta|null]: The content meta data if available
   */
  const EVENT_REMOVE = 'storage.permanent.remove';

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

      if( empty( $format ) ) $meta = isset( $this->meta[ $namespace ] ) ? $this->meta[ $namespace ] : new Helper\ConverterMeta( $this->_format );
      else {

        // don's create new converter if the format is the same
        if( isset( $this->meta[ $namespace ] ) && $this->meta[ $namespace ]->format == $format ) $meta = $this->meta[ $namespace ];
        else $meta = new Helper\ConverterMeta( $format );
      }

      $index = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
      $value = $this->getObject( $index );

      // trigger before save event for custom storage
      $extension = Extension::instance( 'framework' );
      $event     = $extension->trigger( self::EVENT_SAVE, [
        'instance'  => $this,
        'namespace' => $namespace,
        'meta'      => &$meta,
        'content'   => &$value
      ] );

      // check the event result
      if( $event->collector->exist() ) throw $event->collector->get();
      else if( !$event->isPrevented() ) {

        // do the native saving
        $content = $this->_converter->serialize( $value, $meta );

        $this->meta[ $namespace ] = $meta;
        $this->write( $content, $namespace );
      }

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

      $meta = null;

      // trigger before load event for custom storage
      $extension = Extension::instance( 'framework' );
      $event     = $extension->trigger( self::EVENT_LOAD, [
        'instance'  => $this,
        'namespace' => $namespace,
        'meta'      => &$meta
      ] );

      // check the event result
      if( $event->collector->exist() ) throw $event->collector->get();
      else if( !$event->isPrevented() ) $content = $this->read( $namespace, $meta );
      else {

        // search for the content in the results
        $content = null;
        foreach( $event as $result ) {

          if( is_string( $result ) ) {
            $content = $result;
            break;
          }
        }
      }

      // check the meta type
      if( !( $meta instanceof Helper\ConverterMeta ) ) throw new Exception\Strict( self::EXCEPTION_MISSING_META, [ 'namespace' => $namespace ] );
      else {

        // do the rest of the work for the loading
        $this->meta[ $namespace ] = $meta;
        $index                    = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
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

      // trigger before load event for custom storage
      $extension = Extension::instance( 'framework' );
      $event     = $extension->trigger( self::EVENT_REMOVE, [
        'instance'  => $this,
        'namespace' => $namespace,
        'meta'      => isset( $this->meta[ $namespace ] ) ? $this->meta[ $namespace ] : null
      ] );

      // check the event result
      if( $event->collector->exist() ) throw $event->collector->get();
      else {

        // call the native destroy if not prevented
        if( !$event->isPrevented() ) $this->destroy( $namespace );

        // do the rest of the remove
        $index = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
        $this->clear( $index );

        unset( $this->meta[ $namespace ] );
      }

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

      // clean any cache for this index to follow the change
      $this->clean( $index );
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
   * Read a namespace data from the permanent storage
   *
   * @param string|null $namespace The namespace
   * @param null        $meta      The meta for the namespace MUST exist after the read
   *
   * @return null|string
   */
  abstract protected function read( $namespace = null, &$meta = null );
  /**
   * Destroy a namespace's permanent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string|null $namespace The namespace
   */
  abstract protected function destroy( $namespace = null );
}
