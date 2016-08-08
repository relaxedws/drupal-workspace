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

  public static $modules = [
    'system',
    'node',
    'user',
    'block',
    'block_content',
    'workspace',
    'multiversion',
    'entity_reference'
  ];

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
			'administer nodes',
			'access content overview',
    ];

    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'tabs_block']);
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'actions_block']);

    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $this->createNodeType('Test', 'test');
    $this->setupWorkspaceSwitcherBlock();
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);
    $session = $this->getSession();

    // Create a published node.
    $this->drupalGet('/node/add/test');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Published node',
    ], t('Save and publish'));
    $page = $session->getPage();
    $page->hasContent('Published node has been created');
    $this->assertTrue($this->isLabelInContentList('Published node'));

    // Create an unpublished node.
    $this->drupalGet('/node/add/test');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Unpublished node',
    ], t('Save as unpublished'));
    $page = $session->getPage();
    $page->hasContent('Unpublished node has been created');
    $this->assertTrue($this->isLabelInContentList('Unpublished node'));

    // Create a target workspace with replication settings.
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

    // Replicate from Live to Target.
    /** @var ReplicatorManager $rm */
    $rm = \Drupal::service('workspace.replicator_manager');
    $rm->replicate($source_pointer, $target_pointer, $task);

    // Verify the correct nodes were replicated.
    $this->switchToWorkspace($target);
    $this->assertTrue($this->isLabelInContentList('Published node'));
    $this->assertFalse($this->isLabelInContentList('Unpublished node'));
  }

  /**
   * Determine if the content list has an entity's label.
   *
   * This assertion can be used to validate a particular entity exists in the
   * current workspace.
   *
   * @todo move into WorkspaceTestUtilities
   */
  protected function isLabelInContentList($label) {
    $this->drupalGet('/admin/content');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    return $page->hasContent($label);
  }

}
