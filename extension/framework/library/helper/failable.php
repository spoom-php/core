<?php namespace Framework\Helper;

/**
 * Interface FailableInterface
 * @package Framework\Helper
 */
interface FailableInterface {

  /**
   * @return \Exception|null
   */
  public function getException();
}
