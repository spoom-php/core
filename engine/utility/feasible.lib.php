<?php namespace Engine\Utility;

use Engine\Extension\Extension;

defined( '_PROTECT' ) or die( 'DENIED!' );

/**
 * Class Feasible
 * @package Engine\Utility
 */
class Feasible extends Extension {

  /**
   * Execute a function by name
   *
   * @param string $name
   * @param mixed  $args
   *
   * @return mixed
   */
  public function execute( $name, $args = null ) {

    // check execution name validity
    if( is_string( $name ) && strlen( $name ) > 0 && $this->prepare( $name ) !== false ) {

      $f = $this->getFunction( $name );

      // check function validity
      if( is_callable( array( $this, $f ) ) ) {
        $reflectionMethod = new \ReflectionMethod( $this, $f );
        if( $reflectionMethod->isProtected() ) $reflectionMethod->setAccessible( true );

        // execute the function
        return $reflectionMethod->invoke( $this, $args );
      }
    }

    return null;
  }

  /**
   * Do some preparation before the execution. It's maybe extended
   * in child class.
   *
   * @param string $name
   *
   * @return bool
   */
  protected function prepare( &$name ) {
    return true;
  }

  /**
   * Get function name based on execution name
   *
   * @param string $name
   *
   * @return string
   */
  protected function getFunction( $name ) {
    return String::toName( $name );
  }
}