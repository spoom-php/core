<?php namespace Spoom\Core\Helper;

use Spoom\Core\Application;
use Spoom\Core\Storage;
use Spoom\Core\StorageInterface;

/**
 * Class Text
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
   * @param bool  $simple True strings or can be converted to string
   *
   * @return bool
   */
  public static function is( $input, bool $simple = false ): bool {
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
  public static function read( $input, ?string $default = null ): ?string {
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
  public static function unique( int $length = null, string $prefix = '', bool $secure = true, string $hash = 'sha256', int $seeds = 64 ): string {

    $result = '';
    do {

      // add a basic random id with predictable random number prefix for more uniqueness
      $raw = $prefix . uniqid( mt_rand(), true );

      // add some unpredictable random strings for available sources
      if( $secure ) try {

        $raw .= random_bytes( $seeds );

      } catch( \Exception $e ) {

        // log: warning
        Application::instance()->getLog()->warning( 'Cannot use random_bytes due to an error', [ 'error' => $e ], static::class );

      }

      // hash the generated string
      $result .= self::hash( $raw, $hash );

    } while( $length && strlen( $result ) < $length );

    return $length ? substr( $result, 0, $length ) : $result;
  }
  /**
   * Generates hash with the basic PHP hash() function but handles the error with exception
   *
   * TODO add optional $length parameter
   *
   * @param string $raw       The input for the hash
   * @param string $algorithm The hashing algorithm name
   *
   * @return string The hashed string
   * @throws \InvalidArgumentException Throws when the hashing algorithm is invalid
   */
  public static function hash( string $raw, string $algorithm = 'sha256' ): string {

    $tmp = @hash( $algorithm, $raw );
    if( !empty( $tmp ) ) return $tmp;
    else throw new \InvalidArgumentException( Text::apply( 'Invalid hash algorithm: {algorithm}; avaliable {list}', [
      'algorithm' => $algorithm,
      'list'      => implode( ',', hash_algos() )
    ] ) );
  }

  /**
   * Insert variables to the input from insertion array used the regexp constant of class
   *
   * TODO implement condition and loop support
   *
   * @param string                        $text     Input string to insert
   * @param array|object|StorageInterface $context  The insertion variables
   * @param string                        $skip     Opener (and closer) characters for blocks in the text that will not processed
   * @param callable|null                 $callback callback to process replaces
   *
   * @return string
   */
  public static function apply( string $text, $context, string $skip = '', ?callable $callback = null ): string {

    //
    $context = Storage::instance( $context );

    $output    = '';
    $delimiter = null;
    for( $i = 0, $length = strlen( $text ); $i < $length; ++$i ) {

      // detect skipped blocks (start and end)
      if( $delimiter == $text{$i} ) $delimiter = null;
      else if( !$delimiter && strpos( $skip, $text{$i} ) !== false ) $delimiter = $text{$i};

      // try to process the insertion
      if( !$delimiter && $text{$i} == '{' ) {

        $buffer = '';
        for( $j = $i + 1; $j < $length && $text{$j} != '}'; ++$j ) {
          $buffer .= $text{$j};
        }
        $i = $j;

        $output .= $callback ? $callback( $buffer, $context ) : $context[ $buffer ];
        continue;
      }

      $output .= $text{$i};
    }

    return $output;
  }
  /**
   * Clear multiply occurance of chars from text and leave only one
   *
   * @param string $text
   * @param string $chars
   *
   * @return string
   */
  public static function reduce( string $text, string $chars = ' ' ): string {
    $text = preg_replace( '/[' . $chars . ']{2,}/', ' ', $text );
    return $text;
  }

  /**
   * Create camelCase version of the input string along the separator(s)
   *
   * @param string          $name
   * @param string|string[] $separator
   *
   * @return string
   */
  public static function camelify( string $name, $separator = '.' ): string {

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
  public static function linkify( string $text ): string {

    // lowercase
    $text = mb_convert_case( $text, MB_CASE_LOWER, 'UTF-8' );

    // change the chars
    $text = preg_replace( [
      // accented characters
      '/á/i', '/é/i', '/ű|ú|ü/i', '/ő|ó|ö/i', '/í/i',
      // whitespaces
      '/[\s]+/',
      // special characters
      '/[^\w\+]+/iu',
      // shrink multiple separators
      '/[\-]+/i', '/[\+]+/i',
      // change the separators next to each other
      '/(\-\+)|(\+\-)/i'
    ], [
      'a', 'e', 'u', 'o', 'i',
      '+',
      '-',
      '-', '+',
      '+'
    ], $text );

    // trim special chars the beginning or end of the string
    $text = trim( $text, ' -+' );

    return $text;
  }
}
