<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\WorkspaceSwitcherTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\multiversion\Entity\Workspace;
use Drupal\simpletest\WebTestBase;

/**
 * Tests workspace switching functionality.
 *
 * @group multiversion
 */
class WorkspaceSwitcherTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'workspace',
  ];

  /**
   * Test that the block displays and switches workspaces.
   * Then test the admin page displays workspaces and allows switching.
   */
  public function testSwitchingWorkspaces() {
    /** @var \Drupal\multiversion\Workspace\WorkspaceManager $workspace_manager */
    $workspace_manager = \Drupal::service('workspace.manager');

    // Login as a user who can administer workspaces.
    $user = $this->drupalCreateUser(['administer workspaces']);
    $this->drupalLogin($user);

    // Create a new workspace to switch to.
    $new_workspace = Workspace::create(['machine_name' => 'new_workspace', 'label' => 'New Workspace', 'type' => 'basic']);
    $new_workspace->save();

    // Add the block to the sidebar.
    $this->drupalPlaceBlock('workspace_switcher_block', [
      'id' => 'workspaceswitcher',
      'region' => 'sidebar_first',
      'label' => 'Workspace switcher',
    ]);

    // Confirm the block shows on the front page.
    $this->drupalGet('<front>');
    $this->assertText('Workspace switcher', 'Block successfully being displayed on the page.');

    // Click the "Default" workspace to switch to it.
    $current_path = \Drupal::service('path.current')->getPath();
    $this->drupalPostForm($current_path, [], t('New Workspace'));

    // Ensure switching a workspace is successful.
    $this->assertEqual('New Workspace', $workspace_manager->getActiveWorkspace()->label());

    // Ensure both workspaces are listed on the collection list.
    $this->drupalGet('admin/structure/workspace');
    $this->assertText('Live (live)', 'Default workspace found.');
    $this->assertText('New Workspace (new_workspace)', 'New Workspace found.');

    // Load the activate form and check the confirmation message.
    $this->drupalGet('admin/structure/workspace/1/activate');
    $this->assertText('Would you like to activate the Live workspace?');

    // Submit the activate form and ensure switching a workspace is successful.
    $this->drupalPostForm('admin/structure/workspace/1/activate', [], t('Activate'));
    $this->assertEqual('Live', $workspace_manager->getActiveWorkspace()->label());

  }
}
