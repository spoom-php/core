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
/**
 * Trait Failable
 * @package Framework\Helper
 */
trait Failable {

  /**
   * @var \Exception|null
   */
  protected $_exception;

  /**
   * @return \Exception|null
   */
  public function getException() {
    return $this->_exception;
  }

  /**
   * @param \Exception|FailableInterface|null $value
   *
   * @return bool
   */
  protected function setException( $value = null ) {
    $this->_exception = $value instanceof FailableInterface ? $value->getException() : $value;
    return !empty( $this->_exception );
  }
}
