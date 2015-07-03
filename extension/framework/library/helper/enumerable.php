<?php namespace Framework\Helper;

use Framework\Page;

/**
 * Class Enumerable
 * @package Framework\Helper
 */
abstract class Enumerable {

  /**
   * Encode array or object to string ( json ). This is now just a proxy but maybe some improvement will be
   * added in future versions
   *
   * @param object|array $object
   * @param int          $options JSON_* constant flags
   *
   * @return string|boolean The JSON string or false on failure
   */
  public static function toJson( $object, $options = 0 ) {

    $result = json_encode( $object, $options );
    if( $result === false ) Page::getLog()->notice( 'Failed JSON encode', [ 'object' => $object ], '\Framework\Helper\Enumerable' ); // log: notice

    return $result;
  }
  /**
   * Converts JSON string into object or array like the normal json_encode but this one may do
   * some pre/post process operation on the string/object in the future
   *
   * @param string $json
   * @param bool   $assoc
   * @param int    $depth
   * @param int    $options
   *
   * @return mixed
   */
  public static function fromJson( $json, $assoc = false, $depth = 512, $options = 0 ) {
    if( version_compare( phpversion(), '5.4.0', '>=' ) ) $json = json_decode( $json, $assoc, $depth, $options );
    else if( version_compare( phpversion(), '5.3.0', '>=' ) ) $json = json_decode( $json, $assoc, $depth );
    else $json = json_decode( $json, $assoc );

    // log: notice
    if( json_last_error() != JSON_ERROR_NONE ) Page::getLog()->notice( 'Failed JSON decode: #{error.code} {error.message}', [
      'string' => $json,
      'error'  => [
        'message' => json_last_error_msg(),
        'code'    => json_last_error()
      ]
    ], '\Framework\Helper\Enumerable' );

    return $json;
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
   * TODO implement a prefer_object feature
   *
   * @param string $xml       The xml string to convert
   * @param array  $attribute Optional array for attribute indexes
   * @param string $version   The xml version number
   * @param string $encoding  The xml encoding
   *
   * @return array
   */
  public static function fromXml( $xml, array &$attribute = [ ], &$version = '1.0', &$encoding = 'UTF-8' ) {

    // collect encoding and version from xml data
    $dom    = new \DOMDocument();
    $object = [ ];

    if( !$dom->load( $xml ) ) Page::getLog()->notice( 'Failed XML decode', [ 'xml' => $xml ], '\Framework\Helper\Enumerable' ); // log: notice
    else {

      $version  = $dom->xmlVersion;
      $encoding = $dom->xmlEncoding;

      // create root element and start the parsing
      $root     = simplexml_load_string( $xml );
      $elements = [ [ &$object, $root, '' ] ];
      while( $next = array_shift( $elements ) ) {
        $container = &$next[ 0 ];
        $element   = $next[ 1 ];
        $key       = $next[ 2 ];

        // handle "recursion" end, and set simple data to the container
        if( !is_object( $element ) || !( $element instanceof \SimpleXMLElement ) || ( !$element->children()->count() && !$element->attributes()->count() ) ) {
          switch( (string) $element ) {
            case 'NULL':
              $container = null;
              continue;
            case 'TRUE':
              $container = true;
              continue;
            case 'FALSE':
              $container = false;
              continue;
            default:
              $container = (string) $element;
              continue;
          }
        }

        // handle item attributes
        foreach( $element->attributes() as $index => $value ) {
          $container[ $index ] = [ ];
          $elements[ ]         = [ &$container[ $index ], $value, $key . '.' . $index ];

          // save to meta for proper write back
          $attribute[ ] = $key . '.' . $index;
        }

        // collect children names and values (it's for find the arrays before add to the queue)
        $tmp = [ ];
        foreach( $element->children() as $value ) {

          /** @var \SimpleXMLElement $value */
          $index = (string) $value->getName();
          if( !isset( $tmp[ $index ] ) ) $tmp[ $index ] = $value;
          else {

            if( !is_array( $tmp[ $index ] ) ) $tmp[ $index ] = [ $tmp[ $index ] ];
            $tmp[ $index ][ ] = $value;
          }
        }

        // walk trough all children data and add them to the queue
        foreach( $tmp as $index => $value ) {
          $container[ $index ] = null;

          if( !is_array( $value ) ) $elements[ ] = [ &$container[ $index ], $value, $key . '.' . $index ];
          else {

            // handle arrays
            $container[ $index ] = [ ];
            foreach( $value as $i => $v ) $elements[ ] = [ &$container[ $index ][ $i ], $v, $key . '.' . $index . '.' . $i ];
          }
        }
      }
    }

    return $object;
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
   * @return \SimpleXMLElement
   */
  public static function toXml( $enumerable, array $attribute = [ ], $root_name = 'xml', $version = '1.0', $encoding = 'UTF-8' ) {

    // create dom and the root element
    $dom = new \DOMDocument( $version, $encoding );
    $dom->appendChild( $root = $dom->createElement( $root_name ) );

    // walk trought the enumerable and build the xml
    $objects = [ (object) [ 'element' => &$root, 'data' => $enumerable, 'name' => $root_name, 'key' => '' ] ];
    while( $object = array_shift( $objects ) ) {
      /** @var \DOMElement $element */
      $element = $object->element;

      // handle xml "leaf"
      if( !is_object( $object->data ) && !is_array( $object->data ) ) {

        if( $object->data === null ) $value = 'NULL';
        else if( $object->data === true ) $value = 'TRUE';
        else if( $object->data === false ) $value = 'FALSE';
        else $value = (string) $object->data;

        if( in_array( $object->key, $attribute ) ) $element->setAttribute( $object->name, $value );
        else $element->appendChild( $dom->createTextNode( $value ) );

        // handle objects and arrays
      } else foreach( $object->data as $index => $value ) {

        // handle attributes, arrays and properties (in this order)
        if( in_array( $object->key . '.' . $index, $attribute ) ) {
          $objects[ ] = (object) [ 'element' => $element, 'data' => $value, 'name' => $index, 'key' => $object->key . '.' . $index ];
        } else if( self::isArray( $value, false ) ) {
          $objects[ ] = (object) [ 'element' => $element, 'data' => $value, 'name' => $index, 'key' => $object->key . '.' . $index ];
        } else {
          $child = $dom->createElement( is_numeric( $index ) ? $object->name : $index );

          $element->appendChild( $child );
          $objects[ ] = (object) [ 'element' => $child, 'data' => $value, 'name' => $child->tagName, 'key' => $object->key . '.' . $index ];
        }
      }
    }

    // create xml from dom
    return simplexml_import_dom( $dom );
  }

  /**
   * Convert enumerable (object or array) into ini formatted string
   *
   * @param object|array $enumerable The input enumerable
   *
   * @return string
   */
  public static function toIni( $enumerable ) {

    $result   = [ ];
    $iterator = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( (array) $enumerable ) );
    foreach( $iterator as $value ) {
      $keys = [ ];
      foreach( range( 0, $iterator->getDepth() ) as $depth ) $keys[ ] = $iterator->getSubIterator( $depth )->key();

      $print     = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value;
      $quote     = is_numeric( $value ) || is_bool( $value ) ? '' : ( !mb_strpos( $value, '"' ) ? '"' : "'" );
      $result[ ] = join( '.', $keys ) . "={$quote}{$print}{$quote}";
    }

    return implode( "\n", $result );
  }
  /**
   * Convert INI formatted string into object
   *
   * @param string $content An ini formatted string
   *
   * @return object
   */
  public static function fromIni( $content ) {

    $result = [ ];
    $ini    = parse_ini_string( $content, false );
    if( !is_array( $ini ) ) Page::getLog()->notice( 'Invalid INI file', [ 'content' => $content ], '\Framework\Helper\Enumerable' ); // log: notice
    else foreach( $ini as $key => $value ) {

      $keys = explode( '.', $key );
      $tmp  = &$result;

      while( $key = array_shift( $keys ) ) {

        if( empty( $keys ) ) break;
        else {

          if( !isset( $tmp[ $key ] ) ) $tmp[ $key ] = [ ];
          $tmp = &$tmp[ $key ];
        }
      }
      $tmp[ $key ] = $value;
    }

    return (object) $result;
  }

  /**
   * Check for the input is a real numeric array
   *
   * @param mixed $data
   * @param bool  $ordered it will check the index ordering, not just the type
   *
   * @return bool true, if the $data was a real array with numeric indexes
   */
  public static function isArray( $data, $ordered = true ) {

    if( !is_array( $data ) ) return false;
    else if( $ordered ) for( $i = 0; $i < count( $data ); ++$i ) {
      if( !isset( $data[ $i ] ) ) return false;
    } else foreach( $data as $i => $value ) {
      if( !is_numeric( $i ) ) return false;
    }

    return true;
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
      
      $tmp = [ ];
      foreach( $input as $k => $e ) {
        $tmp[ $k ] = self::is( $e ) ? self::copy( $e ) : $e;
      }
      
      $input = $tmp;
      
    } else if( is_object( $input ) ) {
      $input = clone $input;

      if( $input instanceof \stdClass ) foreach( $input as $k => $e ) {
        $input->{$k} = self::is( $e ) ? self::copy( $e ) : $e;
      }
    }

    return $input;
  }

  /**
   * Test if the variable is an enumerable (array or object)
   *
   * @param mixed $test
   *
   * @return bool True if the test is an enumerable
   */
  public static function is( $test ) {
    return is_array( $test ) || is_object( $test );
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

        // handle new key check for two different data type
        $key = $tokens[ $i ];
        if( is_array( $result->container ) ) { // handle like an array

          if( !isset( $result->container[ $key ] ) ) {
            if( $build ) $result->container[ $key ] = [ ];
            else return $result;
          }

          $result->container = &$result->container[ $key ];

        } else if( is_object( $result->container ) ) {   // handle like an object

          if( !isset( $result->container->{$key} ) ) {
            if( $build ) $result->container->{$key} = [ ];
            else return $result;
          }

          $result->container = &$result->container->{$key};
        } else {

          if( $build ) $result->container = [ ];
          else return $result;
        }
      }

      // select key if container exist
      if( self::is( $result->container ) ) {

        $key = $tokens[ $count - 1 ];
        if( is_array( $result->container ) ) {
          if( !isset( $result->container[ $key ] ) ) {
            if( $build ) $result->container[ $key ] = null;
            else return $result;
          }
        } else {
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
