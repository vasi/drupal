<?php

/**
 * @file
 * Contains \Drupal\Tests\dblog\Unit\DbLogTest.
 */

namespace Drupal\Tests\dblog\Unit;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LogMessageParser;
use Drupal\dblog\Logger\DbLog;
use Drupal\Tests\UnitTestCase;
use Psr\Log\InvalidArgumentException;

/**
 * A class that isn't string convertible.
 */
class NotStringConvertible {
}

/**
 * A class that is string convertible.
 */
class StringConvertible {
  public function __toString() {
    return 'convertible';
  }
}

/**
 * @coversDefaultClass \Drupal\dblog\Logger\DbLog
 * @group dblog
 */
class DbLogTest extends UnitTestCase {
  /**
   * @var \Drupal\Core\Database\Connection $conn
   */
  protected $conn;

  /**
   * @var \Drupal\dblog\Logger\DbLog $dblog
   */
  protected $dblog;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface $channel
   */
  protected $channel;

  /**
   * @var array $variables
   *   The variables field from the last DB insert.
   */
  protected $variables;

  /**
   * @var string[] $methods
   *   The available logging methods.
   */
  protected $methods = [
    'emergency',
    'alert',
    'critical',
    'error',
    'warning',
    'info',
    'debug',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // A mock DB can does fake inserts.
    $this->conn = $this->getMockBuilder('\Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->setMethods(['insert', 'fields', 'execute'])
      ->getMockForAbstractClass();
    // Return self, so we can chain calls.
    $this->conn->expects($this->any())->method($this->anything())->willReturnSelf();
    // Store the variables field in an instance var. This may seem like it's
    // abusing internals, but we actually vend the entire contents of this
    // table to the outside world in DBLogResource.
    $this->conn->method('fields')->will($this->returnCallback(function($fields) {
      $this->variables = unserialize($fields['variables']);
      return $this->conn;
    }));

    $parser = new LogMessageParser();
    $this->dblog = new DbLog($this->conn, $parser);

    $this->channel = new LoggerChannel('test');
    $this->channel->addLogger($this->dblog);
  }

  /**
   * Test that we enforce string-ish placeholder substitutions.
   */
  public function testPlaceholderValidation() {
    foreach ($this->methods as $method) {
      $this->assertLogThrows(FALSE, $method, 'no parameters');
      $this->assertLogThrows(FALSE, $method, ':h href parameter',
        [':h' => 'string']);
      $this->assertLogThrows(FALSE, $method, '%em int em parameter',
        ['%em' => 123]);
      $this->assertSame('123', $this->variables['%em'],
        "Parameter was converted to string");
      $this->assertNotSame(123, $this->variables['%em'],
        "Parameter does not remain an int");
      $this->assertLogThrows(FALSE, $method, '@gen float general parameter',
        ['@gen' => -5.4]);
      $this->assertLogThrows(FALSE, $method,
        'multiple convertible params: @a :b %c',
        ['@a' => NULL, ':b' => TRUE, '%c' => 'foo']);
      $this->assertSame('', $this->variables['@a'],
        "Null was converted to string");
      $this->assertLogThrows(FALSE, $method, 'psr {param}',
        ['param' => 'foo']);
      $this->assertLogThrows(FALSE, $method, 'convertible @object',
        ['@object' => new StringConvertible()]);
      $this->assertSame('convertible', $this->variables['@object'],
        "Object was converted to string");

      $this->assertLogThrows(TRUE, $method, 'array @a', ['@a' => []]);
      $this->assertLogThrows(TRUE, $method, 'obj :b',
        [':b' => new NotStringConvertible()]);
      $this->assertLogThrows(TRUE, $method, 'closure %c',
        ['%c' => function() { }]);
      $this->assertLogThrows(TRUE, $method, 'resource @a',
        ['@a' => fopen('php://memory', 'r+')]);
      $this->assertLogThrows(TRUE, $method, 'mixed @a @b @c',
        ['@a' => 123, '@b' => [1], '@c' => TRUE]);
    }
  }

  /**
   * Assert that logging something does (or does not) throw an exception.
   */
  protected function assertLogThrows($throws, $method, $message, $context = array()) {
    try {
      $this->channel->$method($message, $context);
      $this->assertFalse($throws, "Logging '$message' does not throw");
    }
    catch (InvalidArgumentException $e) {
      $this->assertTrue($throws, "Logging '$message'' throws");
    }
  }

}
