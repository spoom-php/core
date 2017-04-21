<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

class LogTest extends TestCase {

  private static $directory = 'LogTest/';

  /**
   * Test severity usage (filtering)
   *
   * @param Log $log
   *
   * @dataProvider providerDefault
   */
  public function testSeverity( Log $log ) {
    $date = date( 'Ymd' );

    $log->setSeverity( Application::SEVERITY_NONE );
    $log->create( "noop", [], 'basic', Application::SEVERITY_EMERGENCY );
    $this->assertFalse( $log->getFile( $date )->exist() );

    $log->setSeverity( Application::SEVERITY_CRITICAL );
    $log->create( "noop", [], 'basic', Application::SEVERITY_ERROR );
    $this->assertFalse( $log->getFile( $date )->exist() );

    $log->create( "foo", [], 'basic', Application::SEVERITY_ALERT );
    $this->assertTrue( $log->getFile( $date )->exist() );
    $this->assertGreaterThan( 0, strlen( $log->getFile( $date )->stream()->read() ) );

    $log->getFile( $date )->remove();
  }

  /**
   * Test data pre-processing
   *
   * @param Log $log
   *
   * @dataProvider providerDefault
   */
  public function testWrap( Log $log ) {

    $tmp = new Converter\Json();
    $this->assertEquals(
      '{"__CLASS__":"Spoom\\\\Core\\\\Converter\\\\Json","-_meta":{' .
      '"__CLASS__":"Spoom\\\\Core\\\\Converter\\\\JsonMeta","+associative":false,"+depth":512,"+options":512},' .
      '"#_exception":null' .
      '}',
      json_encode( $log->wrap( $tmp ) )
    );
  }

  //
  public function providerDefault() {

    $file = new File( __DIR__ );
    return [
      [ new Log( $file->get( static::$directory ), 'test', Application::SEVERITY_DEBUG ) ]
    ];
  }
}
