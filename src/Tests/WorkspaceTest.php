<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\WorkspaceTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the workspace entity.
 *
 * @group workspace
 */
class WorkspaceTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['workspace'];

  public function testSpecialCharacters() {
    $this->webUser = $this->drupalCreateUser([
      'administer workspaces',
    ]);
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/structure/workspaces/add');
    $workspace1 = [
      'label' => 'Workspace 1',
      'machine_name' => 'a0_$()+-/',
    ];
    $this->drupalPostForm('admin/structure/workspace/add', $workspace1, t('Save'));

    $this->drupalGet('admin/structure/workspace');
    $this->assertText($workspace1['label'], 'Workspace found in list of workspaces');

    $workspace2 = [
      'label' => 'Workspace 2',
      'machine_name' => 'A!"£%^&*{}#~@?',
    ];
    $this->drupalPostForm('admin/structure/workspace/add', $workspace2, t('Save'));

    $this->drupalGet('admin/structure/workspace');
    $this->assertNoText($workspace2['label'], 'Workspace not found in list of workspaces');
  }
}