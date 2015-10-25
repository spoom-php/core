<?php namespace Framework\Helper;

use Framework\Exception\Strict;
use Framework\Request;
use Framework\Storage;

/**
 * Class String
 * @package Framework\Helper
 */
abstract class String {

  const EXCEPTION_ERROR_HASH_INVALID_ALGORITHM = 'framework#8E';

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
   * @param string        $text      input string to insert
   * @param array|Storage $insertion the insertion variables
   * @param int           $type
   *
   * @return array|string
   */
  public static function insert( $text, $insertion, $type = self::TYPE_INSERT_EMPTY ) {

    // every insertion converted to data
    if( !( $insertion instanceof Storage ) ) $insertion = new Storage( $insertion );

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
          $ifnull = $value[ 0 ];
          break;
      }

      // replace the pattern
      $text = str_replace( $value[ 0 ], $insertion->getString( $value[ 1 ], $ifnull ), $text );
    }

    return $text;
  }

  /**
   * Generate unique string from available sources and hash it for normalization. Use $secure = true parameter to ensure
   * unpredicatable string is being generated (this is the default, but it's slower)
   *
   * @param int|null $length The length of the result. This will be the default length of the hashing method if NULL
   * @param string   $prefix Custom prefix that helps the uniqueness
   * @param bool     $secure Add unpredictable random values (from available sources) to the raw data or not
   * @param string   $hash   The hashing method name
   *
   * @return string The unique string
   * @throws Strict Throws ::EXCEPTION_ERROR_HASH_INVALID_ALGORITHM when the hashing algorithm is invalid
   */
  public static function unique( $length = null, $prefix = '', $secure = true, $hash = 'sha256' ) {

    // add a basic random id with predictable random number prefix for more uniqueness
    $raw = $prefix . uniqid( mt_rand(), true );

    // add some unpredictable random strings for available sources
    if( $secure ) {

      // try ssl first
      if( !function_exists( 'openssl_random_pseudo_bytes' ) ) {

        // log: warning
        Request::getLog()->warning( 'Cannot use OpenSSL random, `openssl_random_pseudo_bytes()` doesn\'t exists.', [ ], '\Framework\Helper\String' );

      } else {
        $tmp = openssl_random_pseudo_bytes( 64, $strong );

        // skip ssl since it wasn't using the strong algo
        if( $strong === true ) $raw .= $tmp;
        else Request::getLog()->notice( 'Generated OpenSSL random value is not strong, what next?', [ ], '\Framework\Helper\String' ); // log: notice
      }

      // try to read from the unix RNG
      if( is_readable( '/dev/urandom' ) ) {
        $tmp = fopen( '/dev/urandom', 'rb' );
        $raw .= fread( $tmp, 64 );
        fclose( $tmp );
      }
    }

    // hash the generated string
    $raw = self::hash( $raw, $hash );
    return $length ? substr( $raw, 0, $length ) : $raw;
  }
  /**
   * Generates hash with the basic PHP hash() function but handle the errors with exception
   *
   * @param string $raw       The input for the hash
   * @param string $algorithm The hashing algorithm name
   *
   * @return string The hashed string
   * @throws Strict Throws ::EXCEPTION_ERROR_HASH_INVALID_ALGORITHM when the hashing algorithm is invalid
   */
  public static function hash( $raw, $algorithm = 'sha256' ) {

    $tmp = @hash( $algorithm, $raw );
    if( !$tmp ) throw new Strict( self::EXCEPTION_ERROR_HASH_INVALID_ALGORITHM, [ 'algorithm' => $algorithm, 'available' => hash_algos() ] );
    else return $tmp;
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
   * Create camelCase version of the input string along the separator(s)
   *
   * @since {?} Add support for multiple separator
   *
   * @param string          $name
   * @param string|string[] $separator
   *
   * @return string
   */
  public static function toName( $name, $separator = '.' ) {

    // preprocess the separator and the name inputs
    $separator = is_array( $separator ) ? $separator : [ $separator ];
    foreach( $separator as &$tmp ) {

      $tmp  = $tmp{0};
      $name = preg_replace( '/\\' . preg_quote( $tmp, '/' ) . '+/i', $tmp, trim( $name, $tmp ) );
    }

    // create the new name
    $return = '';
    for( $i = 0, $length = mb_strlen( $name ); $i < $length; ++$i ) {
      if( in_array( $name{$i}, $separator ) ) $return .= mb_strtoupper( $name{++$i} );
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

    $source = [
      '/á/i', '/é/i', '/ű|ú|ü/i', '/ő|ó|ö/i', '/í/i', // accented characters
      '/[\W]+/i',                                     // special characters
      '/[\s]+/'                                       // whitespaces
    ];
    $target = [ 'a', 'e', 'u', 'o', 'i', '-', '+' ];

    // convert text
    $text = mb_convert_case( $text, MB_CASE_LOWER, 'UTF-8' ); // lowercase
    $text = preg_replace( $source, $target, $text );          // change the chars
    $text = preg_replace( '/[+\-]+/i', '-', $text );          // clean special chars next to each other
    $text = trim( $text, ' -+' );                             // trim special chars the beginning or end of the string

    return $text;
  }
}
