<?php namespace Engine\Utility;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Enumerable
 * @package Engine\Utility
 */
abstract class Enumerable {

  /**
   * Encode array or object to string ( json )
   *
   * @param object|array $object
   * @param bool         $human_readable
   * @param int          $indent_spaces
   * @param string       $linebreak
   *
   * @return string
   */
  public static function toJson( $object, $human_readable = false, $indent_spaces = 4, $linebreak = "\n" ) {

    // JSON-é alakítás, még minified alakban
    $minified_json = json_encode( $object );

    if( !$human_readable ) return $minified_json;

    // JSON "szépítés" előkészítése
    $dept   = 0;
    $tab    = str_repeat( ' ', $indent_spaces );
    $pieces = preg_split( '/(?<!\\\\)\"/', $minified_json );
    $length = count( $pieces );

    // Végigmegyünk minden második szétválasztott darabon
    for( $i = 0; $i < $length; $i += 2 ) {

      $str = $pieces[ $i ];
      $p   = '';
      $len = strlen( $str );

      // A darbokon végigmegyünk, és szerkesztünk :D
      for( $j = 0; $j < $len; $j++ ) {

        switch( $str[ $j ] ) {
          case '{':
          case '[':
            $p .= $str[ $j ] . $linebreak . str_repeat( $tab, ++$dept );
            break;
          case ',':
            $p .= $str[ $j ] . $linebreak . str_repeat( $tab, $dept );
            break;
          case ':':
            $p .= $str[ $j ] . ' ';
            break;
          case '}':
          case ']':
            $p .= $linebreak . str_repeat( $tab, --$dept ) . $str[ $j ];
            break;
          default:
            $p .= $str[ $j ];
        }
      }

      $pieces[ $i ] = $p;
    }

    return implode( '"', $pieces );
  }

  /**
   * Deep copy of an array or an object
   *
   * @param array|object $array
   *
   * @return array|object
   */
  public static function copy( $array ) {
    $arr = null;

    if( is_array( $array ) ) {
      $arr = array();

      foreach( $array as $k => $e ) {
        $arr[ $k ] = is_object( $e ) || is_array( $e ) ? self::copy( $e ) : $e;
      }
    }
    else if( is_object( $array ) ) {
      $arr = new \stdClass();

      foreach( $array as $k => $e ) {
        $arr->{$k} = is_object( $e ) || is_array( $e ) ? self::copy( $e ) : $e;
      }
    }

    return $arr;
  }

  /**
   * Converts JSON string into object or array like the normal json_encode but this one may do
   * some pre/post process operation on the string/object in the future
   *
   * @param string $json
   * @param bool $assoc
   * @param int $depth
   * @param int $options
   *
   * @return mixed
   */
  public static function fromJson( $json, $assoc = false, $depth = 512, $options = 0 ) {
    if( version_compare(phpversion(), '5.4.0', '>=')) $json = json_decode($json, $assoc, $depth, $options);
    elseif( version_compare(phpversion(), '5.3.0', '>=')) $json = json_decode($json, $assoc, $depth);
    else $json = json_decode($json, $assoc);

    return $json;
  }
}