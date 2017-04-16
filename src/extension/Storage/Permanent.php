<?php namespace Spoom\Framework\Storage;

use Spoom\Framework\Application;
use Spoom\Framework\ConverterInterface;
use Spoom\Framework\Exception;
use Spoom\Framework\Extension;
use Spoom\Framework\Helper;
use Spoom\Framework\Helper\Collection;
use Spoom\Framework\Storage;
use Spoom\Framework\StorageInterface;
use Spoom\Framework\StorageMeta;
use Spoom\Framework\StorageMetaSearch;

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
   * @param string|null $format
   *
   * @return $this
   */
  public function save( ?string $namespace = null, ?string $format = null );
  /**
   * Load a namespace into the storage
   *
   * @param string|null $namespace The namespace to load
   *
   * @return $this
   */
  public function load( ?string $namespace = null );
  /**
   * Remove the namespace data from the storage and from the permanent storage
   *
   * @param string|null $namespace
   *
   * @return $this
   */
  public function remove( ?string $namespace = null );

  /**
   * Get the available converters (value) for formats (key)
   *
   * @return ConverterInterface[]
   */
  public function getConverterMap(): array;
  /**
   * Autoload the namespaces or not
   *
   * @return bool
   */
  public function isAuto(): bool;
  /**
   * Set new value to autoload the namespaces or not
   *
   * @param bool $value
   */
  public function setAuto( bool $value );
  /**
   * Get the default converter format
   *
   * @return string
   */
  public function getFormat(): string;
  /**
   * Set the default converter format
   *
   * @param string $value
   */
  public function setFormat( string $value );
}
/**
 * Class Permanent
 * @package Framework\Storage
 *
 * @since   0.6.0
 *
 * TODO add static cache for the load mechanism to optimize the process
 * TODO add write- and readable features
 * TODO add support for full index save/load/remove?!
 *
 * @property-read Exception|null       $exception The latest exception object
 * @property-read ConverterInterface[] $converter_map
 * @property      bool                 $auto      Autoload the namespaces or not
 * @property      string               $format    The default format for saving
 */
abstract class Permanent extends Storage implements PermanentInterface {
  use Helper\Failable;

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
   * @var ConverterInterface[]
   */
  protected $converter_cache = [];
  /**
   * The converter list. Store the available converters (value) for formats (key)
   *
   * @var ConverterInterface[]
   */
  protected $_converter_map;
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
   * @param object|array         $source     The initial data
   * @param bool                 $caching
   * @param ConverterInterface[] $converters Default converters for the permanent storage. The first converter will be the default format
   */
  public function __construct( $source, bool $caching = true, array $converters = [] ) {
    parent::__construct( $source, $caching );

    // setup the converters
    $this->_converter_map = $converters;
    if( count( $converters ) ) {

      // first will be the default format
      reset( $converters );
      $this->setFormat( key( $converters ) );
    }
  }

  /**
   * Clone the converter and all of the stored meta
   */
  public function __clone() {
    parent::__clone();

    $this->_converter_map  = Collection::copy( $this->_converter_map );
    $this->converter_cache = Collection::copy( $this->converter_cache );
  }

  //
  public function save( ?string $namespace = null, ?string $format = null ) {
    $this->setException();

    try {

      // get or create the converter
      $tmp       = $this->_converter_map[ $format ?: $this->_format ];
      $converter = $this->converter_cache[ $namespace ][ 'converter' ] ?? null;
      if( $converter != $tmp ) $converter = clone $tmp;

      if( empty( $converter ) ) throw new PermanentExceptionConverter( $namespace );
      else {

        $content = $this[ $namespace ? ( $namespace . static::SEPARATOR_NAMESPACE ) : '' ];

        // trigger before save event for custom storage
        $extension = Extension::instance();
        $event     = $extension->trigger( static::EVENT_SAVE, [
          'instance'  => $this,
          'namespace' => $namespace,
          'converter' => $converter,
          'content'   => $content
        ] );

        $converter = $event->get( 'converter', $converter );
        $content   = $event->get( 'content', $content );

        // check the event result
        if( $event->getException() ) throw $event->getException();
        else if( !$event->isPrevented() ) {

          // save and perform the conversion
          $content = $converter->serialize( $content );
          if( $converter->getException() ) throw $converter->getException();
          else $this->converter_cache[ $namespace ] = [ 'format' => $format ?: $this->_format, 'converter' => $converter ];

          // do the native saving
          $this->write( $content, $namespace );
        }
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    return $this;
  }
  //
  public function load( ?string $namespace = null ) {
    $this->setException();

    try {

      $cache                               = $this->converter_cache[ $namespace ] ?? null;
      $this->converter_cache[ $namespace ] = null;

      // trigger before load event for custom storage
      $extension = Extension::instance();
      $event     = $extension->trigger( static::EVENT_LOAD, [
        'instance'  => $this,
        'namespace' => $namespace,
        'converter' => $cache[ 'converter' ]
      ] );

      $content              = $event[ 'content' ];
      $cache[ 'converter' ] = $event->getObject( 'converter', $cache[ 'converter' ] );

      // check the event result
      if( $event->getException() ) throw $event->getException();
      else {

        // read the namespace's data, and check for the converter
        if( !$event->isPrevented() ) {

          $content = $this->read( $namespace );
          $cache   = $this->converter_cache[ $namespace ] ?? null;
        }

        // check the converter
        if( !empty( $content ) && !( $cache[ 'converter' ] instanceof ConverterInterface ) ) throw new PermanentExceptionConverter( $namespace );
        else {

          // convert and set the namespace's data
          if( !empty( $content ) ) {

            $content = $cache[ 'converter' ]->unserialize( $content );
            if( $cache[ 'converter' ]->getException() ) throw $cache[ 'converter' ]->getException();
            else $this->converter_cache[ $namespace ] = $cache;
          }

          $this[ $namespace ? ( $namespace . static::SEPARATOR_NAMESPACE ) : '' ] = $content;
        }
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    return $this;
  }
  //
  public function remove( ?string $namespace = null ) {
    $this->setException();

    try {

      // trigger before load event for custom storage
      $extension = Extension::instance();
      $event     = $extension->trigger( static::EVENT_REMOVE, [
        'instance'  => $this,
        'namespace' => $namespace,
        'converter' => $this->converter_cache[ $namespace ][ 'converter' ] ?? null
      ] );

      // check the event result
      if( $event->getException() ) throw $event->getException();
      else {

        // call the native destroy if not prevented
        if( !$event->isPrevented() ) $this->delete( $namespace );

        // do the rest of the remove
        unset( $this[ $namespace ? ( $namespace . static::SEPARATOR_NAMESPACE ) : '' ] );
        unset( $this->converter_cache[ $namespace ] );
      }

    } catch( \Exception $e ) {
      $this->setException( $e );
    }

    return $this;
  }

  /**
   * @inheritdoc. Setup the autoloader for namespaces
   */
  protected function search( StorageMeta $meta, bool $build = false, bool $is_read = true ): StorageMetaSearch {

    // try to load the storage data if there is no already
    $namespace = $meta->namespace;
    if( $this->isAuto() && !array_key_exists( $namespace, $this->converter_cache ) ) {

      $this->load( $namespace );
      if( $this->getException() ) {

        // log exceptions for autoloading
        Exception::log( $this->getException(), Application::instance()->getLog() );
      }
    }

    // delegate problem to the parent
    return parent::search( $meta, $build, $is_read );
  }

  //
  public function getConverterMap(): array {
    return $this->_converter_map;
  }
  //
  public function isAuto(): bool {
    return $this->_auto;
  }
  //
  public function setAuto( bool $value ) {
    $this->_auto = $value;
  }
  //
  public function getFormat(): string {
    return $this->_format;
  }
  //
  public function setFormat( string $value ) {
    $this->_format = $value;
  }

  /**
   * Write out the string content to the permantent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string      $content   The content to write out
   * @param string|null $namespace The namespace of the content
   */
  abstract protected function write( string $content, ?string $namespace = null );
  /**
   * Read a namespace data from the permanent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string|null $namespace The namespace
   *
   * @return null|string
   */
  abstract protected function read( ?string $namespace = null ): ?string;
  /**
   * Destroy a namespace's permanent storage. The meta for the namespace MAY available through the meta property
   *
   * @param string|null $namespace The namespace
   */
  abstract protected function delete( ?string $namespace = null );
}

/**
 * There is no converter for a namespace to able to read/write the data
 *
 * @package Framework\Storage
 */
class PermanentExceptionConverter extends Exception\Logic {

  const ID = '23#spoom-framework';

  /**
   * @param string|null $namespace The namespace that has no converter
   */
  public function __construct( ?string $namespace ) {

    $data = [ 'namespace' => $namespace ];
    parent::__construct( '(Un)serialization failed, due to an error', static::ID, $data, null, Application::SEVERITY_WARNING );
  }
}
