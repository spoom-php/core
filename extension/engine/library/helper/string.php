<?php namespace Engine\Helper;

use Engine\Storage\Data;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class String
 * @package Engine\Utility
 */
abstract class String {

  /**
   * Regexp for string insertion
   */
  const REGEXP_INSERT_REPLACE = '/\{([a-z0-9_.-]+)\}/i';

  /**
   *  Leave the pattern itself when no data exist for it
   */
  const TYPE_INSERT_LEAVE = 0;
  /**
   * Change the pattern to empty string when no data exist for it
   */
  const TYPE_INSERT_EMPTY = 1;

  /**
   * Insert variables to the input from insertion array used the regexp constant of class
   *
   * @param string     $text      input string to insert
   * @param array|Data $insertion the insertion variables
   * @param int        $type
   *
   * @return array|string
   */
  public static function insert( $text, $insertion, $type = self::TYPE_INSERT_EMPTY ) {
    
    // every insertion converted to data
    if( !($insertion instanceof Data) ) $insertion = new Data( $insertion );
    
    // find patterns iterate trough the matches
    preg_match_all( self::REGEXP_INSERT_REPLACE, $text, $matches, PREG_SET_ORDER );
    foreach( $matches as $value ) {

      // define the default value
      switch( $type ) {
        case self::TYPE_INSERT_EMPTY:
          $ifnull = '';
          break;
        case self::TYPE_INSERT_LEAVE:
        default:
          $ifnull = $value[0];
      }
      
      // replace the pattern
      $text = str_replace( $value[ 0 ], $insertion->getString( $value[ 1 ], $ifnull ), $text );
    }

    return $text;
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

    for( $i = 0, $length = mb_strlen( $name ); $i < $length; ++$i ) {
      if( $name{$i} === $separator ) $return .= mb_strtoupper( $name{++$i} );
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