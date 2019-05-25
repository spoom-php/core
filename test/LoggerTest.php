<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {

  /**
   * Test severity usage (filtering)
   */
  public function testSeverity() {
    $logger = new Logger( 'test' );

    $logger->setSeverity( Severity::NONE );
    $logger->create( "noop", null, 'basic', Severity::EMERGENCY );
    $this->assertEmpty( $logger->getList(), "There shouldn't be any buffered entry, due to `Severity::NONE` severity" );

    $logger->setSeverity( Severity::CRITICAL );
    $logger->create( "noop", null, 'basic', Severity::ERROR );
    $this->assertEmpty( $logger->getList(), "There shouldn't be any buffered entry, due to `Severity::CRITICAL` severity which is higher than `Severity::ERROR`" );

    $logger->create( "foo", null, 'basic', Severity::ALERT );
    $this->assertNotEmpty( $logger->getList(), "There should be an entry because `Severity::ALERT` higher than `Severity::CRITICAL`" );
  }

  /**
   * Test buffering mechanism and file flushing with the File logger
   */
  public function testFile() {
    $logger = new Logger\File( new File( __DIR__ . '/LoggerTest/' ), 'file', Severity::DEBUG );

    // remove any remain file log
    $logger->getFile()->remove();

    $logger->debug( 'foo{bar}', [ 'bar' => 'BAR' ] );
    $this->assertFileNotExists( (string) $logger->getFile(), "There shouldn't be any file due to buffering" );
    $logger->flush();
    $this->assertFileExists( (string) $logger->getFile(), "There must be a file with the entry after flushing" );

    // remove the file to cleanup
    $logger->getFile()->remove();
  }
}
