<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\simpletest\BrowserTestBase;
use Drupal\multiversion\Entity\WorkspaceInterface;

/**
 * Tests access bypass permission controls on workspaces.
 *
 * @group workspace
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 */
class WorkspaceBypassTest extends BrowserTestBase {
  use WorkspaceTestUtilities;

  public static $modules = ['workspace', 'multiversion'];

  /**
   * Verifies that a user can view their own workspace.
   */
  public function testViewOwnWorkspace() {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
    ];

    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);


    $bears = $this->createWorkspaceThroughUI('Bears', 'bears');


  }


  /**
   * Sets a given workspace as "active" for subsequent requests.
   *
   * @param WorkspaceInterface $workspace
   *   The workspace to set active.
   */
  protected function switchToWorkspace(WorkspaceInterface $workspace) {

    // Add the block to the sidebar.
    $this->drupalPlaceBlock('workspace_switcher_block', [
      'id' => 'workspaceswitcher',
      'region' => 'sidebar_first',
      'label' => 'Workspace switcher',
    ]);

    // Confirm the block shows on the front page.
    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasContent('Workspace switcher'));

    $this->drupalPostForm('/', [], t($workspace->label()));



  }
}
