<?php

class FrameworkStorageTest extends PHPUnit_Framework_TestCase {

  public function __construct( $name = null, array $data = [ ], $dataName = '' ) {
    \Framework::execute( function () {
    } );

    parent::__construct( $name, $data, $dataName );
  }

  /**
   * @dataProvider provider
   *
   * @param \Framework\Storage $storage
   */
  public function testBasic( $storage ) {

    // test simple getters
    $this->assertEquals( 2, $storage->getNumber( 'test1.test2' ) );
    $this->assertEquals( 'test', $storage->getString( 'test1.test8' ) );
    $this->assertEquals( [ 'test4' => 4, 'test5' => 5 ], $storage->getArray( 'test1.test3' ) );
    $this->assertEquals( (object) [ 'test4' => 4, 'test5' => 5 ], $storage->getObject( 'test1.test3' ) );
    $this->assertEquals( (object) [ 'test11' => 11, 'test12' => 12 ], $storage->getObject( 'test0' ) );
    $this->assertEquals( true, $storage->getBoolean( 'test1.test6' ) );
    $this->assertEquals( function () {
    }, $storage->getCallable( 'test1.test7' ) );
    $this->assertEquals( 9, $storage->getInteger( 'test1.test20' ) );
    $this->assertEquals( 9, $storage->get( 'test1.test20!int' ) );
    $this->assertEquals( 9.9, $storage->getFloat( 'test1.test20' ), '', 0.1 );
    $this->assertEquals( 9.9, $storage->get( 'test1.test20!float' ), '', 0.1 );

    // test invalid values
    $this->assertEquals( 0, $storage->getNumber( 'test1.test9' ) );
    $this->assertEquals( '', $storage->getString( 'test1.test10' ) );
    $this->assertEquals( '', $storage->getString( 'test1.test10' ) );

    // test namespace switching
    $storage->namespace = 'test1';
    $this->assertEquals( '', $storage->getString( 'test1.test8' ) );
    $this->assertEquals( 'test', $storage->getString( 'test8' ) );
    $this->assertEquals( 'test', $storage->getString( 'test1:test8' ) );
    $storage->namespace = null;
    $this->assertEquals( 'test', $storage->getString( 'test1.test8' ) );
    $this->assertEquals( '', $storage->getString( 'test8' ) );

    // test value changing
    $storage->set( 'test1.test9', 9 );
    $this->assertEquals( 9, $storage->get( 'test1.test9' ) );
    $this->assertEquals( 9, $storage->get( 'test1.test9' ) );
    $storage->set( 'test0.test12', 1212 );
    $this->assertEquals( 1212, $storage->get( 'test0.test12' ) );

    // test for structural change
    $this->assertEquals( 4, $storage->get( 'test1.test3.test4' ) );
    $storage->set( 'test1.test3', 3 );
    $this->assertEquals( null, $storage->get( 'test1.test3.test4' ) );
    $this->assertEquals( 3, $storage->get( 'test1.test3' ) );

    // test for existance checks
    $this->assertEquals( true, $storage->exist( 'test1.test3' ) );
    $this->assertEquals( false, $storage->exist( 'test1.test11' ) );
    $storage->clear( 'test1.test3' );
    $this->assertEquals( false, $storage->exist( 'test1.test3' ) );

    // check for arrayaccess behavior
    $this->assertEquals( 1212, $storage[ 'test0.test12' ] );
    $storage[ 'test0.test12' ] = 121212;
    $this->assertEquals( 121212, $storage[ 'test0.test12' ] );
    unset( $storage[ 'test0.test12' ] );
    $this->assertEquals( false, isset( $storage[ 'test0.test12' ] ) );

    // test the storage in storage access and modification
    $storage2 = new \Framework\Storage( [ 'test30' => [ 'test0' => 'test0' ] ] );
    $storage->set( 'test30', $storage2 );
    $this->assertEquals( 'test0', $storage->get( 'test30.test30.test0' ) );
    $storage->set( 'test30.test30.test1', 'test1' );
    $this->assertEquals( 'test1', $storage->get( 'test30.test30.test1' ) );
    $this->assertEquals( 'test1', $storage2->get( 'test30.test1' ) );
    $storage->set( 'test30.test30.test0', 'test2' );
    $this->assertEquals( 'test2', $storage->get( 'test30.test30.test0' ) );
    $this->assertEquals( 'test2', $storage2->get( 'test30.test0' ) );
  }
  /**
   * @dataProvider provider
   *
   * @param \Framework\Storage $storage
   */
  public function testClone( $storage ) {

    $storage2 = clone $storage;

    // test object references after clone
    $storage->set( 'test1.test2', 22 );
    $this->assertEquals( 2, $storage2->getNumber( 'test1.test2' ) );
  }
  /**
   * @dataProvider provider
   *
   * @param \Framework\Storage $storage
   */
  public function testExtend( $storage ) {

    $storage->extend( 'test1.test2', 2 );
    $this->assertEquals( 4, $storage->get( 'test1.test2' ) );

    $storage->extend( 'test1.test8', '8' );
    $this->assertEquals( 'test8', $storage->get( 'test1.test8' ) );

    $storage->extend( 'test0', [ 'test13' => 13 ] );
    $this->assertEquals( (object) [ 'test11' => 11, 'test12' => 12, 'test13' => 13 ], $storage->get( 'test0' ) );

    // test custom object extend behavior
    $storage2 = new \Framework\Storage( [ 'test14' => 14 ] );
    $storage->extend( 'test0', $storage2 );
    $this->assertEquals( (object) ( [
      'test11' => 11,
      'test12' => 12,
      'test13' => 13,
      'test14' => 14
    ] ), $storage->get( 'test0' ) );
  }
  /**
   * @dataProvider provider
   *
   * @param \Framework\Storage $storage
   */
  public function testEach( $storage ) {

    $storage->each( function ( $key, $value, $index ) {
      $this->assertEquals( 'test11', $key );
      $this->assertEquals( 11, $value );
      $this->assertEquals( 'test0.test11', $index );

      return false;
    }, 'test0' );
  }

  public function provider() {
    return [
      [ new \Framework\Storage( [
        'test0' => (object) [
          'test11' => 11,
          'test12' => 12
        ],
        'test1' => [
          'test2'  => 2,
          'test3'  => [
            'test4' => 4,
            'test5' => 5
          ],
          'test6'  => true,
          'test7'  => function () {
          },
          'test8'  => 'test',
          'test20' => 9.9,
        ]
      ], null, \Framework\Storage::CACHE_NONE ) ],
      [ new \Framework\Storage( [
        'test0' => (object) [
          'test11' => 11,
          'test12' => 12
        ],
        'test1' => [
          'test2'  => 2,
          'test3'  => [
            'test4' => 4,
            'test5' => 5
          ],
          'test6'  => true,
          'test7'  => function () {
          },
          'test8'  => 'test',
          'test20' => 9.9,
        ]
      ], null, \Framework\Storage::CACHE_SIMPLE ) ]
    ];
  }
}
