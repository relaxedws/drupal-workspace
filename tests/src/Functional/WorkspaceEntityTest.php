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

  protected $profile = 'standard';

  /**
   * Tests creating and loading nodes.
   *
   * @dataProvider nodeEntityTestCases
   */
  public function testNodeEntity($initial_workspace) {
    $permissions = [
      'administer nodes',
      'create_workspace',
      'edit_any_workspace',
      'view_any_workspace',
    ];

    $this->createNodeType('Test', 'test');
    $this->setupWorkspaceSwitcherBlock();

    $buster = $this->drupalCreateUser(array_merge($permissions, ['create test content']));

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($buster);

    $workspaces = [
      'live' => $this->getOneWorkspaceByLabel('Live'),
      'stage' => $this->getOneWorkspaceByLabel('Stage'),
      'dev' => $this->createWorkspaceThroughUI('Dev', 'dev'),
    ];
    $default = \Drupal::getContainer()->getParameter('workspace.default');
    $this->switchToWorkspace($workspaces[$initial_workspace]);
    
    $workspace_manager = \Drupal::service('workspace.manager');
    $this->assertEquals($initial_workspace, $workspace_manager->getActiveWorkspace());

    $vanilla_node = $this->createNodeThroughUI('Vanilla node', 'test');
    $this->assertEquals($initial_workspace, $vanilla_node->workspace->target_id);

    $this->drupalGet('/node');
    $this->assertSession()->pageTextContains('Vanilla node');
    $this->drupalGet('/node/' . $vanilla_node->id());
    $this->assertSession()->pageTextContains('Vanilla node');

    foreach ($workspaces as $workspace_id => $workspace) {
      if ($workspace_id != $initial_workspace) {
        $this->switchToWorkspace($workspace);

        $node_list = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties(['title' => $vanilla_node->label()]);
        // @TODO: make this work
        //$this->assertSame(FALSE, reset($node_list));
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadUnchanged($vanilla_node->id());
        // @TODO: make this work
        // Maybe this should return NULL or FALSE?
        //$this->assertSame($vanilla_node->getRevisionId(), $node->getRevisionId());

        if ($initial_workspace == $default) {
          $this->drupalGet('/node');
          $this->assertSession()->statusCodeEquals(200);
          $this->assertSession()->pageTextContains('Vanilla node');
          $this->drupalGet('/node/' . $vanilla_node->id());
          $this->assertSession()->statusCodeEquals(200);
          $this->assertSession()->pageTextContains('Vanilla node');
        }
        else {
          $this->drupalGet('/node');
          $this->assertSession()->statusCodeEquals(200);
          // @TODO: make this work
          //$this->assertSession()->pageTextNotContains('Vanilla node');
          $this->drupalGet('/node/' . $vanilla_node->id());
          $this->assertSession()->statusCodeEquals(403);
          $this->assertSession()->pageTextNotContains('Vanilla node');
        }
      }
    }
  }

  public function nodeEntityTestCases() {
    return [
      ['live'],
      ['stage'],
      ['dev'],
    ];
  }
}
