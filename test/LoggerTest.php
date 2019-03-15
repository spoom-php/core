<?php namespace Spoom\Core;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase {

  /**
   * Test severity usage (filtering)
   */
  public function testSeverity() {
    $logger = new Logger( 'test' );

    $logger->setSeverity( Application::SEVERITY_NONE );
    $logger->create( "noop", null, 'basic', Application::SEVERITY_EMERGENCY );
    $this->assertEmpty( $logger->getList(), "There shouldn't be any buffered entry, due to `Application::SEVERITY_NONE` severity" );

    $logger->setSeverity( Application::SEVERITY_CRITICAL );
    $logger->create( "noop", null, 'basic', Application::SEVERITY_ERROR );
    $this->assertEmpty( $logger->getList(), "There shouldn't be any buffered entry, due to `Application::SEVERITY_CRITICAL` severity which is higher than `Application::SEVERITY_ERROR`" );

    $logger->create( "foo", null, 'basic', Application::SEVERITY_ALERT );
    $this->assertNotEmpty( $logger->getList(), "There should be an entry because `Application::SEVERITY_ALERT` higher than `Application::SEVERITY_CRITICAL`" );
  }

  /**
   * Test buffering mechanism and file flushing with the File logger
   */
  public function testFile() {
    $logger = new Logger\File( new File( __DIR__ . '/LoggerTest/' ), 'file' );

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
