<?php namespace Framework\Helper;

use Framework\Application;
use Framework\Storage;
use Framework\StorageInterface;

/**
 * Class Text
 * @package Framework\Helper
 */
abstract class Text {

  /**
   * Regexp for string insertion
   */
  const INSERT_REGEXP = '/\{([a-z0-9_.-]+)\}/i';

  /**
   * Check if the input is string, or can be a string. Valid strings is:
   *  - numbers (with $simple == false)
   *  - objects with __toString() method (with $simple == false)
   *
   * @param mixed $input
   * @param bool  $simple True strings or converted
   *
   * @return bool
   */
  public static function is( $input, $simple = false ) {
    if( is_string( $input ) ) return true;
    else if( !$simple ) return Number::is( $input, true ) || ( is_object( $input ) && method_exists( $input, '__toString' ) );
    else return false;
  }

  /**
   * Convert the input into string value. On error/invalid input returns the $default parameter
   *
   * @param mixed       $input
   * @param string|null $default
   *
   * @return string|null
   */
  public static function read( $input, $default = null ) {
    switch( true ) {
      case is_string( $input ):
        return $input;
      case Number::is( $input, true ):
        return Number::write( $input );
      case is_object( $input ) && method_exists( $input, '__toString' ):
        return (string) $input;
      case is_resource( $input ):
        $tmp = stream_get_contents( $input );
        if( $tmp !== false ) return $tmp;
    }

    return $default;
  }

  /**
   * Generate unique string from available sources and hash it for normalization. Use $secure = true parameter to ensure
   * unpredicatable string is being generated (this is the default, but it's slower)
   *
   * @param int|null $length The length of the result. This will be the default length of the hashing method if NULL
   * @param string   $prefix Custom prefix that helps the uniqueness
   * @param bool     $secure Add unpredictable random values (from available sources) to the raw data or not
   * @param string   $hash   The hashing method name
   * @param int      $seeds  The minimum length of the secure random seeds (only used when the $secure param is true)
   *
   * @return string The unique string
   * @throws \InvalidArgumentException Throws when the hashing algorithm is invalid
   */
  public static function unique( $length = null, $prefix = '', $secure = true, $hash = 'sha256', $seeds = 64 ) {

    $result = '';
    do {

      // add a basic random id with predictable random number prefix for more uniqueness
      $raw = $prefix . uniqid( mt_rand(), true );

      // add some unpredictable random strings for available sources
      if( $secure ) {

        // try ssl first
        if( !function_exists( 'openssl_random_pseudo_bytes' ) ) {

          // log: warning
          Application::instance()
                     ->getLog()
                     ->warning( 'Cannot use OpenSSL random, `openssl_random_pseudo_bytes()` doesn\'t exists.', [], 'framework:helper.string' );

        } else {
          $tmp = openssl_random_pseudo_bytes( $seeds, $strong );

          // skip ssl since it wasn't using the strong algo
          if( $strong === true ) $raw .= $tmp;
          else Application::instance()
                          ->getLog()
                          ->notice( 'Generated OpenSSL random value is not strong, what next?', [], 'framework:helper.string' ); // log: notice
        }

        // try to read from the unix RNG
        if( is_readable( '/dev/urandom' ) ) {
          $tmp = fopen( '/dev/urandom', 'rb' );
          $raw .= fread( $tmp, $seeds );
          fclose( $tmp );
        }
      }

      // hash the generated string
      $result .= self::hash( $raw, $hash );

    } while( $length && strlen( $result ) < $length );

    return $length ? substr( $result, 0, $length ) : $result;
  }
  /**
   * Generates hash with the basic PHP hash() function but handles the error with exception
   *
   * @param string $raw       The input for the hash
   * @param string $algorithm The hashing algorithm name
   *
   * @return string The hashed string
   * @throws \InvalidArgumentException Throws when the hashing algorithm is invalid
   */
  public static function hash( $raw, $algorithm = 'sha256' ) {

    $tmp = @hash( $algorithm, $raw );
    if( !empty( $tmp ) ) return $tmp;
    else throw new \InvalidArgumentException( Text::insert( 'Invalid hash algorithm: {algorithm}; avaliable {list}', [
      'algorithm' => $algorithm,
      'list'      => implode( ',', hash_algos() )
    ] ) );
  }

  /**
   * Insert variables to the input from insertion array used the regexp constant of class
   *
   * @param string                        $text      Input string to insert
   * @param array|object|StorageInterface $insertion The insertion variables
   * @param bool                          $keep      Keep the missing insertions, or replace them with empty string
   *
   * @return array|string
   */
  public static function insert( $text, $insertion, $keep = false ) {

    // every insertion converted to data
    if( !( $insertion instanceof StorageInterface ) ) {
      $insertion = new Storage( $insertion );
    }

    // find patterns iterate trough the matches
    preg_match_all( self::INSERT_REGEXP, $text, $matches, PREG_SET_ORDER );
    foreach( $matches as $value ) {

      // replace the pattern
      $text = str_replace( $value[ 0 ], $insertion->getString( $value[ 1 ], $keep ? $value[ 0 ] : '' ), $text );
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
      '/á/i', '/é/i', '/ű|ú|ü/i', '/ő|ó|ö/i', '/í/i',     // accented characters
      '/[\s]+/',                                          // whitespaces
      '/[^\w\+]+/iu',                                     // special characters
      '/[\-]+/i',                                         // shrink multiple separators
      '/[\+]+/i',
      '/(\-\+)|(\+\-)/i'                                  // change the separators next to each other
    ];
    $target = [ 'a', 'e', 'u', 'o', 'i', '+', '-', '-', '+', '+' ];

    // convert text
    $text = mb_convert_case( $text, MB_CASE_LOWER, 'UTF-8' );   // lowercase
    $text = preg_replace( $source, $target, $text );            // change the chars
    $text = trim( $text, ' -+' );                               // trim special chars the beginning or end of the string

    return $text;
  }
}
