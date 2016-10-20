<?php namespace Framework\Helper;

/**
 * Class Number
 * @package Framework\Helper
 */
abstract class Number {

  /**
   * Check if the input is a true number (real or integer) or can be a number
   *
   * @param mixed   $input
   * @param boolean $simple True numbers or converted
   *
   * @return boolean
   */
  public static function is( $input, $simple = false ) {
    if( is_integer( $input ) || is_real( $input ) ) return true;
    else if( !$simple ) {

      if( is_bool( $input ) ) return true;
      else if( Text::is( $input, true ) ) return strlen( $input ) > 0 && ltrim( $input, '+-,.e0123456789' ) == '';
    }

    return false;
  }
  /**
   * Check if the input is true integer number or can be converted from string into integer
   *
   * @param mixed $input
   *
   * @return boolean
   */
  public static function isInteger( $input ) {
    return self::is( $input ) && (string) (int) $input === (string) $input;
  }
  /**
   * Check if the input is true real number or can be converted from string into real
   *
   * @param mixed $input
   *
   * @return boolean
   */
  public static function isReal( $input ) {
    return is_real( self::read( $input ) );
  }

  /**
   * Convert the input into integer or real value. On error/invalid input returns the $default parameter
   *
   * @param mixed       $input
   * @param number|null $default Return value on error/invalid input
   *
   * @return number|null
   */
  public static function read( $input, $default = null ) {
    switch( true ) {
      case is_integer( $input ) || is_real( $input ):
        return $input;
      case is_bool( $input ):
        return $input ? 1 : 0;
      case Text::is( $input, true ) && strlen( $input ) > 0 && ltrim( $input, '+-,.e0123456789' ) == '':
        $tmp = floatval( str_replace( ',', '.', $input ) );
        return self::isInteger( $tmp ) ? (int) $tmp : $tmp;
    }

    return $default;
  }
  /**
   * Convert numbers to string
   *
   * @param mixed    $input
   * @param int|null $precision Decimal count if not null
   * @param mixed    $default   Non-number inputs results
   *
   * @return string|mixed
   */
  public static function write( $input, $precision = null, $default = null ) {
    if( !self::is( $input ) ) return $default;
    else {

      $input = self::read( $input );
      if( is_integer( $precision ) ) return number_format( $input, $precision, '.', '' );
      else if( is_integer( $input ) ) return (string) $input;
      else return str_replace( ',', '.', (string) $input );
    }
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
}
