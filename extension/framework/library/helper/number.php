<?php namespace Framework\Helper;

/**
 * Class Number
 * @package Framework\Helper
 */
abstract class Number {

  /**
   * Check if the input is a true number (real or integer) or can be converted from string. The string conversion DOESN'T ignore
   * whitespaces or non-numeric postfixes
   *
   * @param mixed $input
   * @param bool  $string Check for string conversion or not
   *
   * @return bool
   */
  public static function is( $input, $string = true ) {
    return is_integer( $input ) || is_float( $input ) || ( $string && is_string( $input ) && !empty( $input ) && ltrim( $input, "+-,.e0123456789" ) == '' );
  }
  /**
   * Check if the input is true integer number or can be converted from string into integer
   *
   * @param mixed $input
   *
   * @return bool
   */
  public static function isInteger( $input ) {
    return self::is( $input ) && (string) (int) $input === (string) $input;
  }
  /**
   * Check if the input is true real number or can be converted from string into real
   *
   * @param mixed $input
   *
   * @return bool
   */
  public static function isReal( $input ) {
    return is_float( self::read( $input ) );
  }

  /**
   * Check if two number is equals. Can handle floats with precision
   *
   * @param mixed $a
   * @param mixed $b
   * @param int   $precision Precision used for the check
   *
   * @return bool
   */
  public static function equal( $a, $b, $precision = 0 ) {
    return self::write( $a, $precision ) == self::write( $b, $precision );
  }

  /**
   * Convert the input into real integer and float value. On error/invalid input returns the $default parameter
   *
   * @param mixed $input   The input to convert
   * @param mixed $default Return value on error/invalid input
   *
   * @return float|int|mixed
   */
  public static function read( $input, $default = null ) {

    if( self::is( $input, false ) ) return $input;
    else if( !self::is( $input ) ) return $default;
    else {

      $tmp = floatval( str_replace( ',', '.', $input ) );
      return self::isInteger( $tmp ) ? (int) $tmp : $tmp;
    }
  }
  /**
   * Convert numbers to string. Non-number inputs remain unchanged
   *
   * @param mixed    $input   The input number (or not number)
   * @param int|null $decimal Decimal count if needed
   *
   * @return string|mixed
   */
  public static function write( $input, $decimal = null ) {

    if( !self::is( $input ) ) return $input;
    else {

      if( !self::is( $input, false ) ) {
        $input = self::read( $input );
      }

      if( is_integer( $decimal ) ) return number_format( $input, $decimal, '.', '' );
      else if( is_integer( $input ) ) return (string) $input;
      else return str_replace( ',', '.', (string) $input );
    }
  }
}
