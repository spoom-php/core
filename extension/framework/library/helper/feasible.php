<?php namespace Framework\Helper;

use Framework\Extension;
use Framework\Page;

/**
 * Trait Feasible
 * @package Framework\Helper
 */
trait Feasible {

  /**
   * Execute a function by name
   *
   * @param string $name
   * @param mixed  $arguments argument(s) passed to the method based on the type. Array: array of arguments; null: no
   *                          argument; else: the first argument
   *
   * @return mixed
   */
  protected function execute( $name, $arguments = null ) {

    // check execution name validity
    if( is_string( $name ) && mb_strlen( $name ) > 0 && $this->prepare( $name ) !== false ) {

      // check function validity
      $method = $this->method( $name );
      if( !is_callable( [ $this, $method ] ) ) {

        // log: warning
        Page::getLog()->warning( 'Missing \'{name}\' executeable', [
          'name'      => $name,
          'arguments' => $arguments,
          'method'    => $method
        ], '\Framework\Helper\Feasible' );

      } else {

        $reflectionMethod = new \ReflectionMethod( $this, $method );
        if( $reflectionMethod->isProtected() ) $reflectionMethod->setAccessible( true );

        // execute the function
        return $reflectionMethod->invokeArgs( $this, is_array( $arguments ) ? $arguments : ( $arguments === null ? [ ] : [ $arguments ] ) );
      }
    }

    return null;
  }

  /**
   * Do some preparation before the execution. It's maybe extended in child class
   *
   * @param string $name
   *
   * @return string
   */
  private function prepare( $name ) {
    return $name;
  }
  /**
   * Get function name based on execution name
   *
   * @param string $name
   *
   * @return string
   */
  private function method( $name ) {
    return String::toName( $name );
  }
}

/**
 * Interface FeasibleInterface
 * @package Framework\Helper
 */
interface FeasibleInterface {

  /**
   * @param string $name
   * @param mixed  $arguments
   *
   * @return mixed
   */
  public function execute( $name, $arguments );
}
