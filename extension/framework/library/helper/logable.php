<?php namespace Framework\Helper;

use Framework\StorageInterface;

/**
 * Interface LogableInterface
 * @package Framework\Helper
 */
interface LogableInterface {

  /**
   * @param array|object|StorageInterface $data
   * @param LogInterface|null             $log
   *
   * @return boolean
   */
  public function log( $data = [], LogInterface $log = null );
}
