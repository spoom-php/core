<?php namespace Spoom\Framework\Helper;

use Spoom\Framework\LogInterface;
use Spoom\Framework\StorageInterface;

/**
 * Interface Helper\LogableInterface
 * @package Framework\Helper
 */
interface LogableInterface {

  /**
   * @param array|object|StorageInterface $data
   * @param LogInterface|null             $log
   *
   * @return bool
   */
  public function log( $data = [], LogInterface $log = null );
}
