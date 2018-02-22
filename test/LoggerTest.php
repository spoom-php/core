<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {

  private static $directory = 'LoggerTest/';

  /**
   * Test severity usage (filtering)
   *
   * @param Logger $logger
   *
   * @dataProvider providerDefault
   */
  public function testSeverity( Logger $logger ) {
    $date = date( 'Ymd' );

    $logger->setSeverity( Application::SEVERITY_NONE );
    $logger->create( "noop", [], 'basic', Application::SEVERITY_EMERGENCY );
    $this->assertFalse( $logger->getFile( $date )->exist() );

    $logger->setSeverity( Application::SEVERITY_CRITICAL );
    $logger->create( "noop", [], 'basic', Application::SEVERITY_ERROR );
    $this->assertFalse( $logger->getFile( $date )->exist() );

    $logger->create( "foo", [], 'basic', Application::SEVERITY_ALERT );
    $this->assertTrue( $logger->getFile( $date )->exist() );
    $this->assertGreaterThan( 0, strlen( $logger->getFile( $date )->stream()->read() ) );

    $logger->getFile( $date )->remove();
  }

  /**
   * Test data pre-processing
   *
   * @param Logger $logger
   *
   * @dataProvider providerDefault
   */
  public function testWrap( Logger $logger ) {

    $tmp = new Converter\Json();
    $this->assertEquals(
      '{"__CLASS__":"Spoom\\\\Core\\\\Converter\\\\Json","-_meta":{' .
      '"__CLASS__":"Spoom\\\\Core\\\\Converter\\\\JsonMeta","+associative":true,"+depth":512,"+options":512}' .
      '}',
      json_encode( $logger->wrap( $tmp ) )
    );
  }

  //
  public function providerDefault() {

    $file = new File( __DIR__ );
    return [
      [ new Logger( $file->get( static::$directory ), 'test', Application::SEVERITY_DEBUG ) ]
    ];
  }
}
