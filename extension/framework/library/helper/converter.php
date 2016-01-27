<?php namespace Framework\Helper;

use Framework\Extension;
use Framework\Request;
use Framework\Storage;

/**
 * Class Converter
 * @package Framework\Helper
 *
 * @since   0.6.0
 *
 * @property bool $native Use only the native converters, do not trigger the event
 */
class Converter extends Library implements FeasibleInterface {
  use Feasible {
    execute as executeFeasible;
  }

  /**
   * Event triggered when a content is being serialized. The result MAY contains string values, and the last result will be used if any. The serialize can
   * be prevented. Arguments:
   *  - content [mixed]: The content to serialize
   *  - meta [ConverterMeta]: The meta for the content
   */
  const EVENT_SERIALIZE = 'helper.converter.serialize';
  /**
   * Event triggered when a string content is being unserialized. The result MAY contains values, and the last result will be used if any. The unserialize can
   * be prevented. Arguments:
   *  - content [string]: The content to unserialize
   *  - meta [ConverterMeta]: The meta for the content
   */
  const EVENT_UNSERIALIZE = 'helper.converter.unserialize';

  /**
   * Use only the native converters, do not trigger the event
   *
   * @var bool
   */
  protected $_native = false;

  /**
   * @inheritDoc
   *
   * @return mixed
   */
  public function execute( $name, $arguments = null ) {
    return $this->executeFeasible( $name, $arguments );
  }

  /**
   * Serialize any content to a formatted (the output format specified by the meta property) string
   *
   * @param mixed         $content The content to serialize
   * @param ConverterMeta $meta    The meta for the serialization
   *
   * @return string|null
   */
  public function serialize( $content, ConverterMeta $meta ) {

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
      ], '\Framework\Helper\Converter' );

      return null;
    }
  }
  /**
   * Unserialize string that in a (meta defined) format into a php value
   *
   * @param string        $content The content to unserialize
   * @param ConverterMeta $meta    The meta data for the unserialization
   *
   * @return mixed
   */
  public function unserialize( $content, ConverterMeta $meta ) {

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
   * @param ConverterMeta $meta    Custom meta storage that allow proper serialization (back) of the xml properties
   *
   * @return string
   */
  protected function serializeXml( $content, ConverterMeta $meta ) {
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
   * @param ConverterMeta $meta    Custom meta storage that allow proper serialization (back) of the xml properties
   *
   * @return mixed
   */
  protected function unserializeXml( $content, ConverterMeta $meta ) {

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
/**
 * Class ConverterMeta
 * @package Framework\Helper
 *
 * @since   0.6.0
 *
 * @property-read string $format The format of the storage
 */
class ConverterMeta extends Storage {

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
