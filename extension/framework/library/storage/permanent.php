<?php namespace Framework\Storage;

use Framework\Exception;
use Framework\Extension;
use Framework\Helper\Enumerable;
use Framework\Helper\Feasible;
use Framework\Helper\FeasibleInterface;
use Framework\Helper\Library;
use Framework\Request;
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
   * @return PermanentConverter
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
 * @property-read Exception|null     $exception The latest exception object
 * @property-read PermanentConverter $converter The converter object that parse and build the input and output object/string
 * @property      bool               $auto      Autoload the namespaces or not
 * @property      string             $format    The default format for saving
 */
abstract class Permanent extends Storage implements PermanentInterface {

  /**
   * Store metadata for loaded namespaces
   *
   * @var PermanentMeta[string]
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
   * @var PermanentConverter
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
   * @param mixed|null              $data      The initial data
   * @param string|null             $namespace The default namespace
   * @param int                     $caching   The caching mechanism
   * @param PermanentConverter|null $converter Custom converter or use the default (null)
   */
  public function __construct( $data = null, $namespace = null, $caching = self::CACHE_SIMPLE, PermanentConverter $converter = null ) {
    parent::__construct( $data, $namespace, $caching );

    $this->_converter = $converter ?: new PermanentConverter();
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

      if( !empty( $format ) ) $meta = new PermanentMeta( $format );
      else $meta = isset( $this->meta[ $namespace ] ) ? $this->meta[ $namespace ] : new PermanentMeta( $this->_format );

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
      if( empty( $this->meta[ $namespace ] ) || !( $this->meta[ $namespace ] instanceof PermanentMeta ) ) ; // TODO error
      else {

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
   * @return PermanentConverter
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

/**
 * Class PermanentMeta
 * @package Framework\Storage
 *
 * @property-read string $format The format of the storage
 */
class PermanentMeta extends Storage {

  /**
   * The storage format
   *
   * @var string
   */
  protected $_format;

  /**
   * @param string $format The format of the storage
   * @param array  $data   The initial data
   */
  public function __construct( $format, $data = [ ] ) {
    parent::__construct( $data, null, self::CACHE_NONE );

    $this->_format = $format;
  }

  /**
   * @return string
   */
  public function getFormat() {
    return $this->_format;
  }
}
/**
 * Class PermanentConverter
 * @package Framework\Storage
 *
 * @property bool $native Use only the native converters, do not trigger the event
 */
class PermanentConverter extends Library implements FeasibleInterface {
  use Feasible;

  /**
   * Event triggered when a content is being serialized. The result MAY contains string values, and the last result will be used if any. The serialize can
   * be prevented. Arguments:
   *  - content [mixed]: The content to serialize
   *  - meta [PermanentMeta]: The meta for the content
   */
  const EVENT_SERIALIZE = 'storage.permanent.converter.serialize';
  /**
   * Event triggered when a string content is being unserialized. The result MAY contains values, and the last result will be used if any. The unserialize can
   * be prevented. Arguments:
   *  - content [string]: The content to unserialize
   *  - meta [PermanentMeta]: The meta for the content
   */
  const EVENT_UNSERIALIZE = 'storage.permanent.converter.unserialize';

  /**
   * Use only the native converters, do not trigger the event
   *
   * @var bool
   */
  protected $_native = false;

  /**
   * Serialize any content to a formatted (the output format specified by the meta property) string
   *
   * @param mixed         $content The content to serialize
   * @param PermanentMeta $meta    The meta for the serialization
   *
   * @return string|null
   */
  public function serialize( $content, PermanentMeta $meta ) {

    // try to call a converter event
    if( !$this->_native ) {

      $extension = Extension::instance( 'framework' );
      $event     = $extension->trigger( self::EVENT_SERIALIZE, [
        'content' => $content,
        'meta'    => $meta
      ] );
      if( $event->prevented || !empty( $event->result ) ) {
        return !empty( $event->result ) ? (string) array_pop( $event->result ) : null;
      }
    }

    // if the event wasn't successfull, try to call the native converters
    $method = 'serialize.' . mb_strtolower( $meta->format );
    if( $this->method( $method, true ) ) return $this->execute( $method, [ 'content' => $content, 'meta' => $meta ] );
    else {

      // log: warning
      Request::getLog()->warning( 'Missing serializer for \'{format}\'', [
        'content' => $content,
        'format'  => $meta->format,
        'meta'    => $meta
      ], '\Framework\Storage\PermanentConverter' );

      return null;
    }
  }
  /**
   * Unserialize string that in a (meta defined) format into a php value
   *
   * @param string        $content The content to unserialize
   * @param PermanentMeta $meta    The meta data for the unserialization
   *
   * @return mixed
   */
  public function unserialize( $content, PermanentMeta $meta ) {

    // try to call a converter event
    if( !$this->_native ) {

      $extension = Extension::instance( 'framework' );
      $event     = $extension->trigger( self::EVENT_UNSERIALIZE, [
        'content' => $content,
        'meta'    => $meta
      ] );
      if( $event->prevented || !empty( $event->result ) ) {
        return !empty( $event->result ) ? (string) array_pop( $event->result ) : null;
      }
    }

    // if the event wasn't successfull, try to call the native converters
    $method = 'unserialize.' . mb_strtolower( $meta->format );
    if( $this->method( $method, true ) ) return $this->execute( $method, [ 'content' => $content, 'meta' => $meta ] );
    else {

      // log: warning
      Request::getLog()->warning( 'Missing unserializer for \'{format}\' permanent storages', [
        'content' => $content,
        'format'  => $meta->format,
        'meta'    => $meta
      ], '\Framework\Storage\PermanentConverter' );

      return null;
    }
  }

  /**
   * Php is a typesecure storage type. What type comes to write out, it will came back as the same type. Other
   * advantage is the data security: No one can see the contents from outside the server, even if the server
   * missconfigured for other storage types. Major disadvantage of this file type is the human read/write ability.
   *
   * @param mixed $content Content to serialize
   *
   * @return string
   *
   */
  protected function serializePhp( $content ) {
    return '<?php /*{' . serialize( $content ) . '}*/';
  }
  /**
   * Unserializer for the PHP format
   *
   * @param string $content Content to unseraialize
   *
   * @return mixed
   */
  protected function unserializePhp( $content ) {
    return unserialize( preg_replace( '/^(<\\?php\\s*\\/\\*{)/i', '', preg_replace( '/(}\\s*\\*\\/)$/i', '', $content ) ) );
  }

  /**
   * A really oldschool format and it is exists only for compatibility reasons, new systems SHOULD NOT be stored in this type. Multi array structure converted
   * ( and parsed back ) into dot separated keys but there is no support for sections ( can't convert it back easily so don't bother with it anyway )
   *
   * @see \Framework\Helper\Enumerable::toIni()
   *
   * @param mixed $content Content to serialize
   *
   * @return string
   */
  protected function serializeIni( $content ) {
    return Enumerable::toIni( $content );
  }
  /**
   * Unserializer for INI format
   *
   * @see \Framework\Helper\Enumerable::fromIni()
   *
   * @param string $content Content to unserialize
   *
   * @return mixed
   */
  protected function unserializeIni( $content ) {
    return Enumerable::fromIni( $content );
  }

  /**
   * Json format serializer. It will write out the json in human readable format, but don't support any comment type. Maybe in the next releases. This is the
   * default and prefered format
   *
   * @see \Framework\Helper\Enumerable::toJson()
   *
   * @param mixed $content Content to serialize
   *
   * @return string
   */
  protected function serializeJson( $content ) {
    return Enumerable::toJson( $content, JSON_PRETTY_PRINT );
  }
  /**
   * Unserializer for JSON format
   *
   * @see \Framework\Helper\Enumerable::fromJson()
   *
   * @param string $content Content to unserialize
   *
   * @return mixed
   */
  protected function unserializeJson( $content ) {

    $json = Enumerable::fromJson( $content, false );
    return $json ? (array) $json : $json;
  }

  /**
   * XML converter based on the Enumberable class xml related methods
   *
   * @see      \Framework\Helper\Enumerable::toXml()
   *
   * @param mixed         $content Content to serialize
   * @param PermanentMeta $meta    Custom meta storage that allow proper serialization (back) of the xml properties
   *
   * @return string
   */
  protected function serializeXml( $content, PermanentMeta $meta ) {
    return Enumerable::toXml(
      $content,
      $meta->getArray( 'attribute' ),
      'storage',
      $meta->getString( 'version', '1.0' ),
      $meta->getString( 'encoding', 'UTF-8' )
    )->asXml();
  }
  /**
   * XML converter based on the Enumberable class xml related methods
   *
   * @see      \Framework\Helper\Enumerable::fromXml()
   *
   * @param string        $content Content to unserialize
   * @param PermanentMeta $meta    Custom meta storage that allow proper serialization (back) of the xml properties
   *
   * @return mixed
   */
  protected function unserializeXml( $content, PermanentMeta $meta ) {

    $attribute = [ ];
    $version   = null;
    $encoding  = null;

    $tmp = Enumerable::fromXml( $content, $attribute, $version, $encoding );
    $meta->set( '', [ 'attribute' => $attribute, 'version' => $version, 'encoding' => $encoding ] );

    return $tmp;
  }

  /**
   * @return boolean
   */
  public function isNative() {
    return $this->_native;
  }
  /**
   * @param boolean $value
   */
  public function setNative( $value ) {
    $this->_native = (bool) $value;
  }
}
