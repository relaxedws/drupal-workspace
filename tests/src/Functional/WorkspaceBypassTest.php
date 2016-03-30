<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\BlockCreationTrait;
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

    //$vanilla_node = $this->createNodeThroughUI('Vanilla node', 'test');

    $bears = $this->createWorkspaceThroughUI('Bears', 'bears');

    $this->switchToWorkspace($bears);

    // Now create a node as editor 1.
    $ditka_bears_node = $this->createNodeThroughUI('Ditka Bears node', 'test');

    print "Ditka node: {$ditka_bears_node->label()}\n";


    $ditka_bears_node_id = $ditka_bears_node->id();

    /*

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
*/

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

  /**
   * Sets a given workspace as "active" for subsequent requests.
   *
   * This assumes that the switcher block has already been setup by calling
   * setupWorkspaceSwitcherBlock().
   *
   * @param WorkspaceInterface $workspace
   *   The workspace to set active.
   */
  protected function switchToWorkspace(WorkspaceInterface $workspace) {
    $this->getSession()->getPage()->findButton($workspace->label())->click();
  }

  protected function createNodeType($label, $machine_name) {
    $node_type = NodeType::create([
      'type' => $machine_name,
      'label' => $label,
    ]);
    $node_type->save();
  }


  /**
   * Creates a node by "clicking" buttons.
   *
   * @todo This seems to break  when called inside a workspace. Why?
   *
   * @param string $label
   * @param string $bundle
   *
   * @return \Drupal\multiversion\Entity\WorkspaceInterface
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function createNodeThroughUI($label, $bundle) {
    $this->drupalGet('/node/add/' . $bundle);

    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());

    $page = $session->getPage();
    $page->fillField('Title', $label);
    $page->findButton(t('Save'))->click();

    $session->getPage()->hasContent($label);

    $node = $this->getOneEntityByLabel('node', $label);

    print "Returning node: {$node->label()}\n";

    return $node;
  }


}
