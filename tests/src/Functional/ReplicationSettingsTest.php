<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\BrowserTestBase;
use Drupal\workspace\ReplicatorManager;

/**
 * @group workspace
 */
class ReplicationSettingsTest extends BrowserTestBase {
  use WorkspaceTestUtilities;
  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  public static $modules = ['system', 'node', 'user', 'block', 'workspace', 'multiversion', 'entity_reference'];

  /**
   * Verify replication settings using the published filter as an example.
   * @todo since this is a copy and pasted test that was revised, verify there
   * isn't any additional cruft that could be removed
   */
  public function testReplicationSettingsPublishedFilter() {
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'create test content',
      'access administration pages',
      'access content overview',
      'administer content types',
    ];

    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $this->createNodeType('Test', 'test');
    $this->setupWorkspaceSwitcherBlock();
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);

    // Create a published node.
    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Published node');
    $page->findButton(t('Save'))->click();
    $page = $session->getPage();
    $page->hasContent('Published node has been created');

    $published_node_live = $this->getOneEntityByLabel('node', 'Published node');
    $this->assertNodeWasCreatedInWorkspace($published_node_live, $live);

    // Create an unpublished node.
    // @todo change this to an unpublished node somehow?
    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Unpublished node');
    $page->findButton(t('Save'))->click();
    $page = $session->getPage();
    $page->hasContent('Unpublished node has been created');

    $unpublished_node_live = $this->getOneEntityByLabel('node', 'Unpublished node');
    $this->assertNodeWasCreatedInWorkspace($published_node_live, $live);

    // Create a workspace with replication settings.
    $this->drupalGet('/admin/structure/workspace/add');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('label', 'Target');
    $page->fillField('machine_name', 'target');
    $page->selectFieldOption('upstream', '1');
    $page->selectFieldOption('edit-pull-replication-settings', 'published');
    $page->selectFieldOption('edit-push-replication-settings', 'published');
    $page->findButton(t('Save'))->click();
    $session->getPage()->hasContent("'Target (target)");
    $target = $this->getOneWorkspaceByLabel('Target');

    $source_pointer = $this->getPointerToWorkspace($live);
    $target_pointer = $this->getPointerToWorkspace($target);

    // Derive a replication task from the source Workspace.
    $task = new ReplicationTask();
    $replication_settings = $target->get('push_replication_settings')->referencedEntities();
    $replication_settings = count($replication_settings) > 0 ? reset($replication_settings) : NULL;
    if ($replication_settings !== NULL) {
      $task->setFilter($replication_settings->getFilterId());
      $task->setParametersByArray($replication_settings->getParameters());
    }

    /** @var ReplicatorManager $rm */
    $rm = \Drupal::service('workspace.replicator_manager');
    // @todo figure out why this line fails the test:
    $rm->replicate($source_pointer, $target_pointer, $task);

    $this->switchToWorkspace($target);

    // @todo figure out why this is needed; on initial investigation the
    // ContentEntityStorageTrait::buildQuery does not have the active workspace
    // set since it's using $this->workspaceId
    drupal_flush_all_caches();

    $test_node_target = $this->getOneEntityByLabel('node', 'Published node');
    $this->assertEquals($target->id(), $test_node_target->get('workspace')->entity->id());
    $this->drupalGet('/admin/content');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->hasContent($test_node_target->label());

    // @todo verify the unpublished node did not get replicated
  }

  protected function assertNodeWasCreatedInWorkspace($node, $workspace) {
    $this->assertEquals($workspace->id(), $node->get('workspace')->entity->id());
    $this->drupalGet('/admin/content');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->hasContent($node->label());
  }
}
