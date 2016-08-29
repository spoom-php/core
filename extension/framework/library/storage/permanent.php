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
   * Save the namespace's actual storage data
   *
   * @param string|null $namespace The namespace to save
   * @param string|null $converter
   *
   * @return $this
   */
  public function save( $namespace = null, $converter = null );
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
   * @return Helper\ConverterCollection
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
 * TODO add support for full index save/load/remove?!
 *
 * @property-read Exception|null             $exception The latest exception object
 * @property-read Helper\ConverterCollection $converter The converter object that parse and build the input and output object/string
 * @property      bool                       $auto      Autoload the namespaces or not
 * @property      string                     $format    The default format for saving
 */
abstract class Permanent extends Storage implements PermanentInterface {

  /**
   * There is no converter for a namespace to able to read/write the data. Data:
   *  - namespace [string]: The namespace that has no converter
   */
  const EXCEPTION_MISSING_CONVERTER = 'framework#23W';

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
   * Store converters for loaded namespaces
   *
   * @var Helper\ConverterInterface[]
   */
  protected $converter_cache = [];
  /**
   * Store the latest exception
   *
   * @var Exception|null
   */
  protected $_exception;
  /**
   * The converter list. Store the available converters
   *
   * @var Helper\ConverterCollection
   */
  protected $_converter;
  /**
   * Auto-load the namespaces or not
   *
   * @var bool
   */
  protected $_auto = true;
  /**
   * The default format for saving
   *
   * @var string
   */
  protected $_format = null;

  /**
   * @param mixed|null                  $data       The initial data
   * @param string|null                 $namespace  The default namespace
   * @param int                         $caching    The caching mechanism
   * @param Helper\ConverterInterface[] $converters Default converters for the permanent storage. The first converter will be the default format
   */
  public function __construct( $data = null, $namespace = null, $caching = self::CACHE_SIMPLE, $converters = [] ) {
    parent::__construct( $data, $namespace, $caching );

    // setup the converters
    $this->_converter = new Helper\ConverterCollection( $converters );
    if( count( $converters ) ) $this->setFormat( $converters[ 0 ]->getFormat() );
  }

  /**
   * Clone the converter and all of the stored meta
   */
  public function __clone() {
    parent::__clone();

    $this->_converter      = clone $this->_converter;
    $this->converter_cache = Enumerable::copy( $this->converter_cache );
  }

  /**
   * Save the namespace's actual storage data in the given (or the default) format
   *
   * @param string|null $namespace The namespace to save
   * @param string|null $format    The format that the converter instance will recognise
   *
   * @return $this
   */
  public function save( $namespace = null, $format = null ) {
    $this->reset();

    try {

      // get or create the converter
      $tmp       = $this->_converter->get( $format ?: $this->_format );
      $converter = isset( $this->converter_cache[ $namespace ] ) ? $this->converter_cache[ $namespace ] : null;
      if( $converter != $tmp ) $converter = clone $tmp;

      if( empty( $converter ) ) throw new Exception\Strict( static::EXCEPTION_MISSING_CONVERTER );
      else {

        $index   = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
        $content = $this->get( $index );

        // trigger before save event for custom storage
        $extension = Extension::instance( 'framework' );
        $event     = $extension->trigger( self::EVENT_SAVE, [
          'instance'  => $this,
          'namespace' => $namespace,
          'converter' => $converter,
          'content'   => $content
        ] );

        $converter = $event->get( 'converter', $converter );
        $content   = $event->get( 'content', $content );

        // check the event result
        if( $event->collector->exist() ) throw $event->collector->get();
        else if( !$event->isPrevented() ) {

          // save and perform the conversion
          $content                             = $converter->serialize( $content );
          $this->converter_cache[ $namespace ] = $converter;

          // do the native saving
          $this->write( $content, $namespace );
        }
      }

    } catch( \Exception $e ) {
      $this->_exception = Exception\Helper::wrap( $e )->log();
    }

    return $this;
  }
  /**
   *
   * @param string|null   * Load the given namespace into the storage
   * $namespace The namespace to load
   *
   * @return $this
   */
  public function load( $namespace = null ) {
    $this->reset();

    try {

      $converter                           = isset( $this->converter_cache[ $namespace ] ) ? $this->converter_cache[ $namespace ] : null;
      $this->converter_cache[ $namespace ] = null;

      // trigger before load event for custom storage
      $extension = Extension::instance( 'framework' );
      $event     = $extension->trigger( self::EVENT_LOAD, [
        'instance'  => $this,
        'namespace' => $namespace,
        'converter' => $converter
      ] );

      $content   = $event->get( 'content', null );
      $converter = $event->get( 'converter', $converter );

      // check the event result
      if( $event->collector->exist() ) throw $event->collector->get();
      else {

        // read the namespace's data, and check for the converter
        if( !$event->isPrevented() ) {

          $content   = $this->read( $namespace );
          $converter = isset( $this->converter_cache[ $namespace ] ) ? $this->converter_cache[ $namespace ] : null;
        }

        // check the converter
        if( !empty( $content ) && ( empty( $converter ) || !( $converter instanceof Helper\ConverterInterface ) ) ) throw new Exception\Strict( self::EXCEPTION_MISSING_CONVERTER, [ 'namespace' => $namespace ] );
        else {

          // convert and set the namespace's data
          if( !empty( $content ) ) {
            $this->converter_cache[ $namespace ] = $converter;
            $content                             = $converter->unserialize( $content );
          }

          $index = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
          $this->set( $index, $content );
        }
      }

    } catch( \Exception $e ) {
      $this->_exception = Exception\Helper::wrap( $e )->log();
    }

    return $this;
  }
  /**
   * Remove the namespace data from the storage with the permanent storage too
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
        'converter' => isset( $this->converter_cache[ $namespace ] ) ? $this->converter_cache[ $namespace ] : null
      ] );

      // check the event result
      if( $event->collector->exist() ) throw $event->collector->get();
      else {

        // call the native destroy if not prevented
        if( !$event->isPrevented() ) $this->destroy( $namespace );

        // do the rest of the remove
        $index = $namespace ? ( $namespace . self::SEPARATOR_NAMESPACE ) : '';
        $this->clear( $index );

        unset( $this->converter_cache[ $namespace ] );
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
    if( $this->isAuto() && !array_key_exists( $index->namespace, $this->converter_cache ) ) {
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
   * @return Helper\ConverterCollection
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
   * Write out the string content to the permantent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string      $content   The content to write out
   * @param string|null $namespace The namespace of the content
   */
  abstract protected function write( $content, $namespace = null );
  /**
   * Read a namespace data from the permanent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string|null $namespace The namespace
   *
   * @return null|string
   */
  abstract protected function read( $namespace = null );
  /**
   * Destroy a namespace's permanent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string|null $namespace The namespace
   */
  abstract protected function destroy( $namespace = null );
}
