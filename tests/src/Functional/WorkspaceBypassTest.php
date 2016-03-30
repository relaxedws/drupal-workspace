<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\BrowserTestBase;
use Drupal\workspace\ReplicatorManager;

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
  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  public static $modules = ['node', 'user', 'block', 'workspace', 'multiversion'];

  /**
   * Verifies that a user can edit anything in a workspace with a specific perm.
   */
  public function testBypassSpecificWorkspace() {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
    ];

    $this->createNodeType('Test', 'test');
    $this->setupWorkspaceSwitcherBlock();

    $ditka = $this->drupalCreateUser(array_merge($permissions, ['create test content']));

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($ditka);

    $vanilla_node = $this->createNodeThroughUI('Vanilla node', 'test');

    $bears = $this->createWorkspaceThroughUI('Bears', 'bears');

    $default = $this->getOneEntityByLabel('workspace', 'Default');

    $this->switchToWorkspace($bears);

    // Now create a node as editor 1.
    $ditka_bears_node = $this->createNodeThroughUI('Ditka Bears node', 'test');
    $ditka_bears_node_id = $ditka_bears_node->id();

    // Replicate all content from the default workspace to Bears.
    /** @var ReplicatorManager $rm */
    $rm = \Drupal::service('workspace.replicator_manager');
    $rm->replicate($this->getPointerToWorkspace($default), $this->getPointerToWorkspace($bears));

    // Create a new user that should be able to edit anything in the Bears workspace.
    $lombardi = $this->drupalCreateUser(array_merge($permissions, ['view_workspace_' . $bears->id(), 'bypass_content_access_workspace_' . $bears->id()]));
    $this->drupalLogin($lombardi);
    $this->switchToWorkspace($bears);

    // Because editor 2 has the bypass permission, he should be able to
    // create and edit any node.

    $this->drupalGet('/node/' . $ditka_bears_node_id . '/edit');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());

    $this->drupalGet('/node/' . $vanilla_node->id() . '/edit');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());

    $this->createNodeThroughUI('Lombardi Bears node', 'test');

    /*

Create Bears workspace
Grant access to Editor 1 and 2 to Bears
Editor 1 creates node
Editor 2 should have access to * on node
Editor 2 creates node
Editor 1 should NOT have access to * on node


     */

  }

  protected function setupWorkspaceSwitcherBlock() {
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
  }


}
