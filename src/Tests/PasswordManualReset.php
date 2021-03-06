<?php

/**
 * @file
 * Definition of Drupal\password_policy\Tests\PasswordManualReset.
 */

namespace Drupal\password_policy\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests manual password reset.
 *
 * @group password_policy
 */
class PasswordManualReset extends WebTestBase {

  public static $modules = array('password_policy', 'node');

  /**
   * Test manual password reset.
   */
  function testManualPasswordReset() {
    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser(array());

    // Create new admin user.
    $user2 = $this->drupalCreateUser(array(
      'manage password reset',
      'administer users',
      'administer permissions'
    ));
    $this->drupalLogin($user2);

    // Create new role.
    $rid = $this->drupalCreateRole(array());

    // Update user 1 by adding role.
    $edit = array();
    $edit['roles[' . $rid . ']'] = $rid;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));

    // Force reset users of new role.
    $edit = array();
    $edit['roles[' . $rid . ']'] = $rid;
    $this->drupalPostForm("admin/config/security/password-policy/reset", $edit, t('Save'));

    // Verify expiration.
    $user = \Drupal::entityManager()->getStorage('user')->load($user1->id());
    $this->assertEqual($user->get('field_password_expiration')[0]->value, "1", 'User password is expired after manual reset');
  }

  /**
   * Test exclude myself.
   */
  function testExcludeMyself() {
    // Create new admin user.
    $user1 = $this->drupalCreateUser(array(
      'manage password reset',
      'administer users',
      'administer permissions'
    ));
    $this->drupalLogin($user1);

    // Create new role.
    $rid = $this->drupalCreateRole(array());

    // Update user 1 by adding role.
    $edit = array();
    $edit['roles[' . $rid . ']'] = $rid;
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));

    // Force reset users of new role with exclude.
    $edit = [
      'roles[' . $rid . ']' => $rid,
      'exclude_myself' => '1',
    ];
    $this->drupalPostForm("admin/config/security/password-policy/reset", $edit, t('Save'));

    // Verify page.
    $this->verbose($this->getUrl());
    $this->assertEqual($this->getUrl(), $this->getAbsoluteUrl('admin/config/security/password-policy'), "User should have been redirected to password policy page");

    // Force reset users of new role without exclude.
    $edit = [
      'roles[' . $rid . ']' => $rid,
      'exclude_myself' => '0',
    ];
    $this->drupalPostForm("admin/config/security/password-policy/reset", $edit, t('Save'));

    // Verify page.
    $this->verbose($this->getUrl());
    $this->assertText('Access denied', "User should have access to the current page");
  }

}
