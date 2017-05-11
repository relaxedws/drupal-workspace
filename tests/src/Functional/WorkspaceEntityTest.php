<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests creating and loading entities in workspaces.
 *
 * @group workspace
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
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

    $buster = $this->drupalCreateUser(array_merge($permissions, ['view own unpublished content', 'create test content', 'edit own test content']));

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

    $strawberry_node = $this->createNodeThroughUI('Strawberry node', 'test', FALSE);
    $this->assertEquals($initial_workspace, $strawberry_node->workspace->target_id);

    $this->drupalGet('/node');
    $this->assertSession()->pageTextNotContains('Strawberry node');
    $this->drupalGet('/node/' . $strawberry_node->id());
    $this->assertSession()->pageTextContains('Strawberry node');

    $chocolate_node = $this->createNodeThroughUI('Chocolate node', 'test', FALSE);
    $this->assertEquals($initial_workspace, $chocolate_node->workspace->target_id);

    $this->drupalGet('/node');
    $this->assertSession()->pageTextNotContains('Chocolate node');
    $this->drupalGet('/node/' . $chocolate_node->id());
    $this->assertSession()->pageTextContains('Chocolate node');

    $this->drupalPostForm('/node/' . $chocolate_node->id() . '/edit', [
      'title[0][value]' => 'Mint node'
    ], t('Save and publish'));

    $this->drupalGet('/node');
    $this->assertSession()->pageTextContains('Mint node');
    $this->drupalGet('/node/' . $chocolate_node->id());
    $this->assertSession()->pageTextContains('Mint node');

    foreach ($workspaces as $workspace_id => $workspace) {
      if ($workspace_id != $initial_workspace) {
        $this->switchToWorkspace($workspace);

        if ($initial_workspace == $default || $workspace_id == $default) {
          // When the node started on the default workspace, or the current
          // workspace is default, entity queries should return the correct
          // revision.
          $node_list = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['title' => $vanilla_node->label()]);
          $this->assertSame($vanilla_node->getRevisionId(), reset($node_list)->getRevisionId());
        }
        else {
          // When the node was created on a non-default workspace and the
          // current workspace is not the default, entity queries should return
          // nothing.
          $node_list = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['title' => $vanilla_node->label()]);
          $this->assertSame(FALSE, reset($node_list));
        }

        // Entity load and load_multiple should always return the default
        // revision.
        $node_load = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($vanilla_node->id());
        $this->assertSame($vanilla_node->getRevisionId(), $node_load->getRevisionId());
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadUnchanged($vanilla_node->id());
        $this->assertSame($vanilla_node->getRevisionId(), $node->getRevisionId());

        if ($initial_workspace == $default) {
          // Then the node was created on the default workspace it should
          // appear via the UI on all other workspaces.
          $this->drupalGet('/node');
          $this->assertSession()->statusCodeEquals(200);
          $this->assertSession()->pageTextContains('Vanilla node');
          $this->drupalGet('/node/' . $vanilla_node->id());
          $this->assertSession()->statusCodeEquals(200);
          $this->assertSession()->pageTextContains('Vanilla node');
        }
        else {
          // When the node was not created on the default it should only not
          // appear via the UI.
          $this->drupalGet('/node');
          $this->assertSession()->statusCodeEquals(200);
          $this->assertSession()->pageTextNotContains('Vanilla node');
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
