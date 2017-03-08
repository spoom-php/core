<?php namespace Spoom\Framework\Helper;

/**
 * Interface FailableInterface
 * @package Framework\Helper
 *
 * @propert \Throwable $exception
 */
interface FailableInterface {

  /**
   * @return \Throwable|null
   */
  public function getException(): ?\Throwable;
  /**
   * @param \Throwable|FailableInterface|null $value
   *
   * @return bool
   */
  public function setException( $value = null ): bool;
}
/**
 * Trait Failable
 * @package Framework\Helper
 */
trait Failable {

  /**
   * @var \Throwable|null
   */
  protected $_exception;

  /**
   * @return \Throwable|null
   */
  public function getException(): ?\Throwable {
    return $this->_exception;
  }
  /**
   * @param \Throwable|FailableInterface|null $value
   *
   * @return bool
   */
  public function setException( $value = null ): bool {
    $this->_exception = $value instanceof FailableInterface ? $value->getException() : $value;
    return !empty( $this->_exception );
  }
}
