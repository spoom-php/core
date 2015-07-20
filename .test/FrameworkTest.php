<?php

class FrameworkTest extends PHPUnit_Framework_TestCase {

  public function testInit() {
    \Framework::setup( function () {
    } );
  }

  /**
   * @depends testInit
   */
  public function testAutoloadExtension() {

    // simple extension class loading
    $this->assertEquals( '\Framework\Storage', \Framework::library( 'framework:storage' ) );

    // nested extension class loading
    $this->assertEquals( '\Framework\Helper\ConverterMeta', \Framework::library( 'Framework\Helper\ConverterMeta' ) );
    $this->assertEquals( '\framework\helper\ConverterMeta', \Framework::library( 'framework:helper.ConverterMeta' ) );
  }

  /**
   * @depends testInit
   */
  public function testAutoloadCustom() {

    // add custom namespace path
    \Framework::connect( 'Custom\NS', \Framework::PATH_BASE . '.test/FrameworkTest/' );

    // try complex named nested class loading from the custom namespace
    $this->assertEquals( '\Custom\NS\POP3MailerClAsS', \Framework::library( 'Custom\NS\POP3MailerClAsS' ) );
    $this->assertEquals( '\Custom\NS\SMTPMailer_Class', \Framework::library( 'Custom\NS\SMTPMailer_Class' ) );

    // try "nested" custom namespace support
    \Framework::connect( 'Custom\NS\Mailer', \Framework::PATH_BASE . '.test/FrameworkTest/m4iler' );
    $this->assertEquals( '\Custom\NS\Mailer\HTML5', \Framework::library( 'Custom\NS\Mailer\HTML5' ) );

    // remove the custom path
    \Framework::disconnect( 'Custom\NS' );
    $this->assertEquals( null, \Framework::library( 'Custom\NS\Invalid' ) );
  }

  /**
   * @depends testInit
   */
  public function testLevel() {

    $this->assertEquals( \Framework::LEVEL_CRITICAL, \Framework::getLevel( 'critical', false ) );
    $this->assertEquals( 'error', \Framework::getLevel( \Framework::LEVEL_ERROR, true ) );

    $this->assertEquals( 'critical', \Framework::getLevel( 'critical', true ) );
    $this->assertEquals( \Framework::LEVEL_ERROR, \Framework::getLevel( \Framework::LEVEL_ERROR, false ) );

    $this->assertEquals( null, \Framework::getLevel( 'csoki', true ) );
  }
}
