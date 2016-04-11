<?php

/**
 * @file
 * Contains \Drupal\Tests\workspace\Functional\ReplicatorTest.
 */

namespace Drupal\Tests\workspace\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\workspace\ReplicatorManager;

/**
 * Test the workspace entity.
 *
 * @group workspace
 */
class ReplicatorTest extends BrowserTestBase {
  use WorkspaceTestUtilities;
  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  public static $modules = ['node', 'user', 'block', 'workspace', 'multiversion', 'taxonomy'];

  /**
   * Verifies that a user can edit anything in a workspace with a specific perm.
   */
  public function testReplication() {
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'create test content',
      'administer taxonomy'
    ];

    $this->createNodeType('Test', 'test');
    Vocabulary::create(['name' => 'Tags', 'vid' => 'tags', 'hierarchy' => 0])->save();

    $this->setupWorkspaceSwitcherBlock();

    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);

    $test_node = $this->createNodeThroughUI('Test node', 'test');
    $test_term1 = Term::create(['name' => 'tag1', 'vid' => 'tags']);
    $test_term1->save();
    $test_term2 = Term::create(['name' => 'tag2', 'vid' => 'tags']);
    $test_term2->save();

    $this->drupalGet('/admin/structure/taxonomy/manage/tags/overview');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $session->getPage()->hasContent($test_term1->label());
    $session->getPage()->hasContent($test_term2->label());

    $target = $this->createWorkspaceThroughUI('Target', 'target');
    $live = $this->getOneEntityByLabel('workspace', 'Live');
    /** @var ReplicatorManager $rm */
    $rm = \Drupal::service('workspace.replicator_manager');
    $rm->replicate($this->getPointerToWorkspace($live), $this->getPointerToWorkspace($target));

    $this->switchToWorkspace($target);

    $test_node_target = $this->getOneEntityByLabel('node', 'Test node');
    $this->drupalGet('/node/' . $test_node_target->id());
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $session->getPage()->hasContent($test_node_target->label());
  }
}
