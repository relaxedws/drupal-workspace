<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\node\Entity\Node;
use Drupal\simpletest\BlockCreationTrait;
use \Drupal\Tests\BrowserTestBase;

/**
 * Tests creating and loading entities in workspaces.
 *
 * @group workspace
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 */
class WorkspaceEntityTest extends BrowserTestBase {
  use WorkspaceTestUtilities;
  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  public static $modules = ['node', 'user', 'block', 'workspace'];

  /**
   * Tests creating and loading nodes.
   */
  public function testNodeEntity() {
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
    ];

    $this->createNodeType('Test', 'test');
    $this->setupWorkspaceSwitcherBlock();

    $buster = $this->drupalCreateUser(array_merge($permissions, ['create test content']));

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($buster);

    $vanilla_node = $this->createNodeThroughUI('Vanilla node', 'test');
    $this->assertEquals(1, $vanilla_node->workspace->target_id);

    $leaf = $this->createWorkspaceThroughUI('Leaf', 'leaf');
    $this->switchToWorkspace($leaf);

    $node_list = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => $vanilla_node->label()]);
    $this->assertSame(FALSE, reset($node_list));
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($vanilla_node->id());
    $this->assertSame($vanilla_node->id(), $node->id());
  }

}
