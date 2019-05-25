<?php namespace Spoom\Core;

class Severity {

  /**
   * The level of silence
   */
  const NONE = 0;
  /**
   * The level of total devastation, when the system is unuseable
   */
  const EMERGENCY = 1;
  /**
   * The level of immediate attention
   */
  const ALERT = 2;
  /**
   * The level of critical problems
   */
  const CRITICAL = 3;
  /**
   * The level of errors
   */
  const ERROR = 4;
  /**
   * The level of warnings
   */
  const WARNING = 5;
  /**
   * The level of nothing serious but still need some attention
   */
  const NOTICE = 6;
  /**
   * The level of useful informations
   */
  const INFO = 7;
  /**
   * The level of detailed informations
   */
  const DEBUG = 8;

  /**
   * Map severity levels to PHP error levels
   *
   * @var array<int,int>
   */
  const MAP = [
    self::NONE      => 0,
    self::EMERGENCY => 0,
    self::ALERT     => 0,
    self::CRITICAL  => E_COMPILE_ERROR | E_PARSE,
    self::ERROR     => E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR,
    self::WARNING   => E_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_USER_WARNING,
    self::NOTICE    => E_NOTICE | E_USER_NOTICE,
    self::INFO      => E_STRICT | E_DEPRECATED | E_USER_DEPRECATED,
    self::DEBUG     => E_ALL
  ];

  /**
   * Converts current (or provided) PHP error level to severity
   */
  public static function get( ?int $reporting = null ): int {
    $reporting = $reporting ?? error_reporting();
    foreach( static::MAP as $severity => $_reporting ) {
      if( $_reporting & $reporting ) return $severity;
    }

    return static::NONE;
  }
}
