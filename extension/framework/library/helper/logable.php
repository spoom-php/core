<?php namespace Framework\Helper;

use Framework\StorageInterface;

/**
 * Interface LogableInterface
 * @package Framework\Helper
 */
interface LogableInterface {

  /**
   * @param array|object|StorageInterface $data
   * @param Log|null                      $log
   *
   * @return boolean
   */
  public function log( $data = [ ], Log $log = null );
}
