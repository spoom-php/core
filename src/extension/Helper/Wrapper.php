<?php namespace Spoom\Core\Helper;

use Spoom\Core\Storage;
use Spoom\Core\StorageInterface;

//
class Wrapper implements \Iterator {

  /**
   * Rename or copy input elements
   *
   * The key is the source element and the value is the target(s) (both read/write the input itself). Values can be an array of targets. You can use
   * StorageInterface index notations both for keys and values
   *
   * @var array
   */
  const PROPERTY_MAP = [];
  /**
   * Wrap input elements to classes
   *
   * The key is the input element and the value is the class to wrap in. The class MUST BE a subclass of `Wrapper` and the input element's value will be the input
   * for that class's `::instance()` method. You can use the [] prefix to wrap the input element's every iterable subelement instead of the whole value
   *
   * @var array
   */
  const PROPERTY_WRAP = [];

  /**
   * @var int
   */
  private $iterator_cursor = 0;
  /**
   * @var array
   */
  private $iterator_key = [];

  /**
   * @param array|object $input
   *
   * @throws \TypeError
   */
  protected function __construct( $input ) {

    // preprocess the input to be able to resolve "deep object links" in mapping
    $input = $input instanceof StorageInterface ? clone $input : new Storage( Collection::cast( $input, [] ) );

    // perform renames and copies in the input
    $this->map( $input, static::PROPERTY_MAP );

    // warp input elements into classes
    $this->wrap( $input, static::PROPERTY_WRAP );

    // fill the object properties from the input
    foreach( $this as $property => $value ) {
      $this->{$property} = $input[ $property ] ?? $value;
    }
  }

  /**
   * Perform renames and copies in the input
   *
   * @param StorageInterface $input
   * @param array            $definition Defined operations
   */
  protected function map( StorageInterface $input, array $definition ) {
    Collection::map( $input, $input, $definition );
  }
  /**
   * Wrap input elements in classes
   *
   * @param StorageInterface $input
   * @param array            $definition Defined operations
   *
   * @throws \TypeError
   */
  protected function wrap( StorageInterface $input, array $definition ) {
    foreach( $definition as $property => $class ) {

      // wrap the whole value in a class, or every subvalue
      $is_array = $class{0} == '[' && $class{1} == ']';
      /** @var self $class */
      $class = ltrim( $class, '[]' );

      // choose between wrap modes
      $value = $input[ $property ];
      if( !class_exists( $class ) || !is_subclass_of( $class, self::class ) ) $input[ $property ] = null;
      else if( !$is_array ) $input[ $property ] = $class::instance( $value );
      else {

        $list = [];
        if( Collection::is( $value, true ) ) {
          foreach( $value as $i => $t ) {
            $list[ $i ] = $class::instance( $t );
          }
        }

        $input[ $property ] = $list;
      }
    }
  }

  //
  public function current() {
    return $this->{$this->key()};
  }
  //
  public function next() {
    ++$this->iterator_cursor;
  }
  //
  public function key() {
    return $this->iterator_key[ $this->iterator_cursor ];
  }
  //
  public function valid() {
    return isset( $this->iterator_key[ $this->iterator_cursor ] );
  }
  //
  public function rewind() {
    $this->iterator_cursor = 0;
    $this->iterator_key    = [];

    // FIXME this can be slow
    $tmp = ( new \ReflectionObject( $this ) )->getProperties( \ReflectionProperty::IS_PUBLIC );
    foreach( $tmp as $t ) $this->iterator_key[] = $t->getName();
  }

  /**
   * @param mixed $input
   *
   * @return static|null
   * @throws \TypeError
   */
  public static function instance( $input ) {
    return $input !== null ? new static( $input ) : null;
  }
}
