<?php namespace Framework\Helper;

use Framework\Application;

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
    if( is_string( $name ) && mb_strlen( $name ) > 0 && ( $name = $this->prepare( $name ) ) !== false ) {

      // check function validity
      $method = $this->method( $name, true );
      if( $method ) return $method->invokeArgs( $this, is_array( $arguments ) ? $arguments : ( $arguments === null ? [ ] : [ $arguments ] ) );
      else {

        // log: warning
        Application::instance()->getLog()->warning( 'Missing \'{name}\' executeable', [
          'name'      => $name,
          'arguments' => $arguments,
          'method'    => $method
        ], 'framework:helper.feasible' );

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
  protected function prepare( $name ) {
    return $name;
  }
  /**
   * Get function name based on execution name
   *
   * @since {?} Support ':' and '-' characters in the name
   *
   * @param string $name
   * @param bool   $instance Return an instance of \ReflectionMethod instead of the method name
   *
   * @return null|\ReflectionMethod|string NULL, if the instance is true and there was no method or private
   */
  protected function method( $name, $instance = false ) {

    $name = Text::toName( $name, [ '.', ':', '-' ] );
    if( !$instance ) return $name;
    else if( !is_callable( [ $this, $name ] ) ) return null;
    else {

      $method = new \ReflectionMethod( $this, $name );
      if( $method->isPrivate() ) return null;
      else {

        if( $method->isProtected() ) $method->setAccessible( true );
        return $method;
      }
    }
  }
}
