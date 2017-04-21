<?php namespace Spoom\Core\Helper;

/**
 * Class Number
 */
abstract class Number {

  /**
   * Check if the input is a true number (real or integer) or can be a number
   *
   * @param mixed $input
   * @param bool  $simple True numbers or converted
   *
   * @return bool
   */
  public static function is( $input, bool $simple = false ): bool {
    if( is_int( $input ) || is_float( $input ) ) return true;
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
   * @return bool
   */
  public static function isInteger( $input ): bool {
    return self::is( $input ) && (string) (int) $input === (string) $input;
  }
  /**
   * Check if the input is true real number or can be converted from string into real
   *
   * @param mixed $input
   *
   * @return bool
   */
  public static function isReal( $input ): bool {
    return is_float( self::read( $input ) );
  }

  /**
   * Convert the input into integer or real value. On error/invalid input returns the $default parameter
   *
   * @param mixed          $input
   * @param int|float|null $default Return value on error/invalid input
   *
   * @return int|float|null
   */
  public static function read( $input, $default = null ) {
    switch( true ) {
      case is_int( $input ) || is_float( $input ):
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
  public static function write( $input, ?int $precision = null, $default = null ) {
    if( !self::is( $input ) ) return $default;
    else {

      $input = self::read( $input );
      if( is_int( $precision ) ) return number_format( $input, $precision, '.', '' );
      else if( is_int( $input ) ) return (string) $input;
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
  public static function equal( $a, $b, int $precision = 0 ): bool {
    return self::write( $a, $precision ) == self::write( $b, $precision );
  }
}
