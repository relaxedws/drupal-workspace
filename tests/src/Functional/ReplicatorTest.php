<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Component\Utility\Crypt;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\workspace\Event\ReplicationEvent;
use Drupal\workspace\Event\ReplicationEvents;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'node',
    'user',
    'block',
    'workspace',
    'multiversion',
    'taxonomy',
    'entity_reference',
    'field',
    'field_ui',
    'menu_link_content',
    'menu_ui',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   *
   */
  public function setUp() {
    parent::setUp();
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'create test content',
      'access administration pages',
      'administer taxonomy',
      'administer menu',
      'access content overview',
      'administer content types',
      'administer node display',
      'administer node fields',
      'administer node form display',
    ];

    $this->createNodeType('Test', 'test');
    $vocabulary = Vocabulary::create(['name' => 'Tags', 'vid' => 'tags', 'hierarchy' => 0]);
    $vocabulary->save();

    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);
    $this->setupWorkspaceSwitcherBlock();

    $this->entityTypeManager = \Drupal::entityTypeManager();
  }

  /**
   * Verifies that a user can edit anything in a workspace with a specific perm.
   */
  public function testReplication() {
    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Test node');
    $page->fillField('Provide a menu link', 1);
    $page->fillField('Menu link title', 'Test node link');
    $page->findButton(t('Save'))->click();
    $page = $session->getPage();
    $page->hasContent("Test node has been created");

    $test_node_live = $this->getOneEntityByLabel('node', 'Test node');
    $this->assertEquals($live->id(), $test_node_live->get('workspace')->entity->id());
    $this->drupalGet('/admin/content');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->hasContent($test_node_live->label());

    $menu_link_live = $this->getOneEntityByLabel('menu_link_content', 'Test node link');
    $this->assertEquals($live->id(), $menu_link_live->get('workspace')->entity->id());
    $this->drupalGet('/admin/structure/menu/manage/main');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->hasContent('Test node link');

    // Imitate an event subscriber to check that events are dispatched.
    // As we are triggering "Deploy" action it will automatically
    // create "Update" replication so events should be dispatched twice.
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $event_dispatcher
      ->dispatch(ReplicationEvents::QUEUED_REPLICATION, Argument::type(ReplicationEvent::class))
      ->shouldBeCalledTimes(2);
    $event_dispatcher
      ->dispatch(ReplicationEvents::PRE_REPLICATION, Argument::type(ReplicationEvent::class))
      ->shouldBeCalledTimes(2);
    $event_dispatcher
      ->dispatch(ReplicationEvents::POST_REPLICATION, Argument::type(ReplicationEvent::class))
      ->shouldBeCalledTimes(2);
    $container = \Drupal::getContainer();
    $container->set('event_dispatcher', $event_dispatcher->reveal());
    \Drupal::setContainer($container);

    $target = $this->createWorkspaceThroughUI('Target', 'target');
    /** @var \Drupal\workspace\ReplicatorManager $rm */
    $rm = \Drupal::service('workspace.replicator_manager');
    $task = new ReplicationTask();
    $rm->replicate($this->getPointerToWorkspace($live), $this->getPointerToWorkspace($target), $task);
    \Drupal::service('cron')->run();

    $replication_log_id = $this->getPointerToWorkspace($live)->generateReplicationId($this->getPointerToWorkspace($target), $task);
    $replication_logs = $this->entityTypeManager->getStorage('replication_log')->getQuery()->allRevisions()->condition('uuid', $replication_log_id)->execute();
    $this->assertEquals(2, count($replication_logs));
    $i = 1;
    foreach ($replication_logs as $revision_id => $id) {
      $this->assertEquals(2, $id);
      $this->assertEquals($i * 2, $revision_id);
      /** @var \Drupal\replication\Entity\ReplicationLogInterface $revision */
      $revision = $this->entityTypeManager->getStorage('replication_log')->loadRevision($revision_id);
      $this->assertTrue($revision->ok->value);
      if ($i == 1) {
        $this->assertNull($revision->getHistory()[0]['docs_written']);
      }
      else {
        $this->assertEquals(2, $revision->getHistory()[0]['docs_written']);
      }
      $i++;
    }

    $this->switchToWorkspace($target);

    $test_node_target = $this->getOneEntityByLabel('node', 'Test node');
    $this->assertEquals($target->id(), $test_node_target->get('workspace')->entity->id());
    $this->drupalGet('/admin/content');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->hasContent($test_node_target->label());

    $menu_link_target = $this->getOneEntityByLabel('menu_link_content', 'Test node link');
    $this->assertEquals($target->id(), $menu_link_target->get('workspace')->entity->id());
    $this->drupalGet('/admin/structure/menu/manage/main');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->hasContent('Test node link');
  }

  function testReplicationBlocker() {
    $test_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($test_user);
    $state = \Drupal::state();
    $state->set('workspace.last_replication_failed', TRUE);
    $key = $state->get('workspace.replication_blocker_key');
    if(!$key) {
      $this->drupalGet('/admin/reports/status/reset-replication-blocker');
      $session = $this->getSession();
      $this->assertEquals(403, $session->getStatusCode());
      $key = Crypt::randomBytesBase64(55);
      $state->set('workspace.replication_blocker_key', $key);
    }
    // Request blocker reset with the wrong key.
    $this->drupalGet('/admin/reports/status/reset-replication-blocker/' . $key . 'foo');
    $session = $this->getSession();
    $this->assertEquals(403, $session->getStatusCode());

    $this->assertTrue($state->get('workspace.last_replication_failed'));

    // Request blocker reset with the correct key.
    $this->drupalGet('/admin/reports/status/reset-replication-blocker/' . $key);
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $this->assertFalse($state->get('workspace.last_replication_failed'));
  }

}
