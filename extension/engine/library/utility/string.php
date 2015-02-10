<?php namespace Engine\Utility;

use Engine\Extension\Localization;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class String
 * @package Engine\Utility
 */
abstract class String {

  /**
   * Regexp for string insertion
   */
  const REGEXP_INSERT_REPLACE = '\{([A-z0-9_.-]+)(?:&([\w]))?\}';

  /**
   * Insert variables to the input from insertion array used the regexp constant of class
   *
   * @param string       $input     input string to insert
   * @param array        $insertion the insertion variables
   * @param Localization $language  optional language object for flags behavior
   *
   * @return array|string
   */
  public static function insert( $input, array $insertion, Localization $language = null ) {

    // define input type for the return
    $is_array = is_array( $input );
    if( !$is_array ) $input = array( $input );

    // iterate input
    foreach( $input as $i => $text ) {
      preg_match_all( '/' . self::REGEXP_INSERT_REPLACE . '/i', $text, $matches, PREG_SET_ORDER );

      // iterate trough the matches
      foreach( $matches as $value ) {

        $insert = isset( $insertion[ $value[ 1 ] ] ) && is_string( $insertion[ $value[ 1 ] ] ) ? $insertion[ $value[ 1 ] ] : $value[ 1 ];

        if( isset( $value[ 2 ] ) ) {
          switch( strtolower( $value[ 2 ] ) ) {
            // insert from language file
            case 'l':
              if( !isset( $language ) ) break;
              $insert = $language->gets( $insert );
              break;
            // let the work for the javascript
            case 'j':
              continue;

            // simple change
            default :
              break;
          }
        }

        $text = str_replace( $value[ 0 ], $insert, $text );
      }

      $input[ $i ] = $text;
    }

    return $is_array ? $input : $input[ 0 ];
  }

  /**
   * Clear multiply occurance of chars from text and leave only one
   *
   * @param string $text
   * @param string $chars
   *
   * @return string
   */
  public static function reduce( $text, $chars = ' ' ) {
    $text = preg_replace( '/[' . $chars . ']{2,}/', ' ', $text );

    return $text;
  }

  /**
   * Create camelCase version of the input string along the separator
   *
   * @param string $name
   * @param string $separator
   *
   * @return string
   */
  public static function toName( $name, $separator = '.' ) {

    // TODO escape only non words
    $name = preg_replace( '/\\' . $separator . '+/i', $separator, trim( $name, $separator ) );
    $return = '';

    for( $i = 0, $length = strlen( $name ); $i < $length; ++$i ) {
      if( $name{$i} === $separator ) $return .= strtoupper( $name{++$i} );
      else $return .= $name{$i};
    }

    return $return;
  }

  /**
   * Convert input text into a link
   *
   * @param string $text
   *
   * @return string
   */
  public static function toLink( $text ) {

    $source = array( '/á/i', '/é/i', '/ű|ú|ü/i', '/ő|ó|ö/i', '/í/i', // accented characters
      '/[\W]+/i',                                     // special characters
      '/[\s]+/' );                                    // whitespaces
    $target = array( 'a', 'e', 'u', 'o', 'i', '-', '+' );

    // convert text
    $text = mb_convert_case( $text, MB_CASE_LOWER, 'UTF-8' ); // lowercase
    $text = preg_replace( $source, $target, $text );          // change the chars
    $text = preg_replace( '/[+\-]+/i', '-', $text );          // clean special chars next to each other
    $text = trim( $text, ' -+' );                             // trim special chars the beginning or end of the string

    return $text;
  }
}