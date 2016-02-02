<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserDisplayNameTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that display name is shown everywhere.
 *
 * @group user
 */
class UserDisplayNameTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'user_hooks_test',
  ];

  /**
   * An authenticated user to use for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access user profiles',
      'administer users',
    ];

    $this->account = $this->drupalCreateUser($permissions);
  }

  /**
   * Test display name.
   */
  function testDisplayName() {
    // Set to test the altered username.
    \Drupal::state()->set('user_hooks_test_user_format_name_alter', TRUE);

    // We must login after the alter hook as been enabled or the username will
    // be shown instead of the display name.
    $this->drupalLogin($this->account);

    // Test if the altered display name is properly shown on pages.
    $this->drupalGet('user/' . $this->account->id());
    $this->assertRaw($this->account->getDisplayName(), 'User view page shows altered user display name.');

    $this->drupalGet('user/' . $this->account->id() . '/edit');
    $this->assertRaw($this->account->getDisplayName(), 'User edit page shows altered user display name.');
  }

