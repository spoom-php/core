<?php namespace Framework\Helper;

use Framework\Converter;
use Framework\StorageInterface;

/**
 * Class Enumerable
 * @package Framework\Helper
 */
abstract class Enumerable {

  /**
   * Encode array or object to string ( json ). This is now just a proxy but maybe some improvement will be
   * added in future versions
   *
   * @param object|array $content
   * @param int          $options JSON_* constant flags
   *
   * @deprecated Use the Framework\Converter\Json class
   * @return string|bool The JSON string or false on failure
   */
  public static function toJson( $content, $options = 0 ) {
    return ( new Converter\Json( $options ) )->serialize( $content );
  }
  /**
   * Converts JSON string into object or array like the normal json_encode but this one may do
   * some pre/post process operation on the string/object in the future
   *
   * @param string $content
   * @param bool   $assoc
   * @param int    $depth
   * @param int    $options
   *
   * @deprecated Use the Framework\Converter\Json class
   * @return mixed
   */
  public static function fromJson( $content, $assoc = false, $depth = 512, $options = 0 ) {
    return ( new Converter\Json( $options, $depth, $assoc ) )->unserialize( $content );
  }

  /**
   * Convert any kind of xml text to array. The attributes of the elements "converted" to simple properties, but
   * their dot separated route from the root element is saved in an optional array for the toXml() method
   *
   * note: It can't handle situations like this: <sometag someattribute="..">Some content</sometag>.
   *       This will be parsed into -> sometag: { someattribute: ".." }, the content will be ignored!
   *
   * note2: if any content equals to 'NULL', 'TRUE' or 'FALSE' will be converted to the proper type
   *
   * @param string $xml       The xml string to convert
   * @param array  $attribute Optional array for attribute indexes
   * @param string $version   The xml version number
   * @param string $encoding  The xml encoding
   *
   * @deprecated Use the Framework\Converter\Xml class
   * @return array
   */
  public static function fromXml( $xml, array &$attribute = [], &$version = '1.0', &$encoding = 'UTF-8' ) {
    $meta             = new Converter\XmlMeta( $version, $encoding );
    $meta->attributes = $attribute;

    $result = ( new Converter\Xml( $meta ) )->unserialize( $xml );

    $attribute = $meta->attributes;
    $version   = $meta->version;
    $encoding  = $meta->encoding;

    return $result;
  }
  /**
   * Parse objects, arrays or strings into an xml object. You can pass an array that contains dot separated routes
   * of attribute type properties in the object.
   *
   * note: true, false or null type will be converted to 'TRUE', 'FALSE' or 'NULL' strings
   *
   * @param mixed  $enumerable The object, array or string
   * @param array  $attribute  Dot separated routes of attributes
   * @param string $root_name  The root element name in the xml
   * @param string $version    Xml version number
   * @param string $encoding   Xml encoding
   *
   * @deprecated Use the Framework\Converter\Xml class
   * @return \SimpleXMLElement
   */
  public static function toXml( $enumerable, array $attribute = [], $root_name = 'xml', $version = '1.0', $encoding = 'UTF-8' ) {
    $meta             = new Converter\XmlMeta( $version, $encoding );
    $meta->root       = $root_name;
    $meta->attributes = $attribute;

    $result = ( new Converter\Xml( $meta ) )->serialize( $enumerable );
    return simplexml_load_string( $result );
  }

  /**
   * Convert enumerable (object or array) into ini formatted string
   *
   * @param object|array $content The input enumerable
   *
   * @deprecated Use the Framework\Converter\Ini class
   * @return string
   */
  public static function toIni( $content ) {
    return ( new Converter\Ini() )->serialize( $content );
  }
  /**
   * Convert INI formatted string into object
   *
   * @param string $content An ini formatted string
   *
   * @deprecated Use the Framework\Converter\Ini class
   * @return object
   */
  public static function fromIni( $content ) {
    return ( new Converter\Ini() )->unserialize( $content );
  }

  /**
   * Test if the variable is an enumerable (array or object)
   *
   * @param mixed $test
   *
   * @return bool True if the test is an enumerable
   */
  public static function is( $test ) {
    return self::isArrayLike( $test ) || is_object( $test );
  }
  /**
   * Check for the input is a real numeric array
   *
   * @param mixed $test
   * @param bool  $ordered it will check the index ordering, not just the type
   *
   * @return bool true, if the $data was a real array with numeric indexes
   */
  public static function isArray( $test, $ordered = true ) {

    if( !is_array( $test ) ) return false;
    else if( $ordered ) for( $i = 0; $i < count( $test ); ++$i ) {
      if( !isset( $test[ $i ] ) ) return false;
    } else foreach( $test as $i => $value ) {
      if( !is_int( $i ) ) return false;
    }

    return true;
  }
  /**
   * Extended array test that includes objects with the \ArrayAccess interface
   *
   * @param mixed $test
   *
   * @since 0.6.4
   * @return bool
   */
  public static function isArrayLike( $test ) {
    return is_array( $test ) || $test instanceof \ArrayAccess;
  }

  /**
   * Deep copy of an array or an object
   *
   * @param array|object $input
   *
   * @return array|object
   */
  public static function copy( $input ) {

    if( is_array( $input ) ) {

      $tmp = [];
      foreach( $input as $k => $e ) {
        $tmp[ $k ] = self::is( $e ) ? self::copy( $e ) : $e;
      }

      $input = $tmp;

    } else if( is_object( $input ) ) {

      if( !( $input instanceof \stdClass ) ) $input = clone $input;
      else {

        $tmp = new \stdClass();
        foreach( $input as $k => $e ) {
          $tmp->{$k} = self::is( $e ) ? self::copy( $e ) : $e;
        }
        $input = $tmp;
      }
    }

    return $input;
  }
  /**
   * Convert the input into array or object value. On error/invalid input returns the $default parameter
   *
   * @since ?
   *
   * @param mixed             $input
   * @param bool              $object Result casted to object or array
   * @param array|object|null $default
   *
   * @return array|object|null
   */
  public static function read( $input, $object = true, $default = null ) {

    if( is_object( $input ) ) {

      // support storages
      if( $input instanceof StorageInterface ) $input = $input->getArray( '' );

      // support arrayaccess
      if( $input instanceof \ArrayAccess ) {

        $tmp = [];
        foreach( $input as $k => $t ) $tmp[ $k ] = $t;
        $input = $tmp;
      }

      // support json seriable objects (at last)
      if( $input instanceof \JsonSerializable ) $input = $input->jsonSerialize();
    }

    return self::is( $input ) ? ( $object ? (object) $input : (array) $input ) : $default;
  }

  /**
   * Search trough an enumerable and return the result
   *
   * @param object|array $enumerable The enumerable to search
   * @param array        $tokens     The search "path" array
   * @param bool         $build      Build the non existed paths or not
   *
   * @return object The { exist: bool, key: string, container: object|array }
   */
  public static function search( &$enumerable, $tokens, $build = false ) {

    // if not index return the whole source
    $result = (object) [ 'exist' => true, 'key' => null, 'container' => &$enumerable ];
    if( count( $tokens ) ) {

      $result->exist = false;
      for( $count = count( $tokens ), $i = 0; $i < $count - 1; ++$i ) {

        // check the container type
        if( !Enumerable::is( $result->container ) ) {

          if( $build ) $result->container = [];
          else return $result;
        }

        // handle new key check for two different data type
        $key = $tokens[ $i ];
        if( is_array( $result->container ) ) { // handle like an array

          if( !isset( $result->container[ $key ] ) ) {
            if( $build ) $result->container[ $key ] = [];
            else return $result;
          }

          $result->container = &$result->container[ $key ];

        } else if( is_object( $result->container ) ) {   // handle like an object

          if( $result->container instanceof StorageInterface ) break;
          else {

            if( !isset( $result->container->{$key} ) ) {
              if( $build ) $result->container->{$key} = [];
              else return $result;
            }

            $result->container = &$result->container->{$key};
          }
        }
      }

      // select key if container exist
      $key = implode( StorageInterface::SEPARATOR_KEY, array_slice( $tokens, $i ) );
      if( self::is( $result->container ) ) {

        if( is_array( $result->container ) ) {
          if( !isset( $result->container[ $key ] ) ) {
            if( $build ) $result->container[ $key ] = null;
            else return $result;
          }
        } else if( !( $result->container instanceof StorageInterface ) ) {
          if( !isset( $result->container->{$key} ) ) {
            if( $build ) $result->container->{$key} = null;
            else return $result;
          }
        }

        // setup the result
        $result->key   = $key;
        $result->exist = true;
      }
    }

    return $result;
  }
}
