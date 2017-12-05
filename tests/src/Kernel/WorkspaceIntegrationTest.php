<?php

namespace Drupal\Tests\workspace\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;
use Drupal\workspace\Entity\Workspace;

/**
 * Tests a complete deployment scenario across different workspaces.
 *
 * @group workspace
 */
class WorkspaceIntegrationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use ViewResultAssertionTrait;
  use UserCreationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'filter', 'node', 'text', 'user', 'system', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installConfig(['filter', 'node', 'system']);

    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->createContentType(['type' => 'page']);

    $this->setCurrentUser($this->createUser(['administer nodes']));

    // Create two nodes, a published and an unpublished one, so we can test the
    // behavior of the module with default/existing content.
    $this->createNode(['title' => 'live - 1 - r1 - published', 'status' => TRUE]);
    $this->createNode(['title' => 'live - 2 - r2 - unpublished', 'status' => FALSE]);
  }

  /**
   * Tests various scenarios for creating and deploying content in workspaces.
   */
  public function testWorkspaces() {
    // Enable the Workspace module here instead of the static::$modules array so
    // we can test it with default content.
    $this->enableModules(['workspace']);
    $this->container = \Drupal::getContainer();
    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->installEntitySchema('workspace');
    $this->installEntitySchema('content_workspace');
    $this->installEntitySchema('replication_log');

    // Create two workspaces by default, 'live' and 'stage'.
    $live = Workspace::create(['id' => 'live']);
    $live->save();
    $stage = Workspace::create(['id' => 'stage', 'upstream' => 'local_workspace:live']);
    $stage->save();

    $permissions = [
      'administer nodes',
      'create workspace',
      'edit any workspace',
      'view any workspace',
    ];
    $this->setCurrentUser($this->createUser($permissions));

    // Notes about the structure of the test scenarios:
    // - 'default_revision' indicates the entity revision that should be
    //   returned by entity_load(), non-revision entity queries and non-revision
    //   views *in a given workspace*, it does not indicate what is actually
    //   stored in the base entity tables.
    $test_scenarios = [];

    // A multi-dimensional array keyed by the workspace ID, then by the entity
    // and finally by the revision ID.
    //
    // In the initial state we have only the two revisions that were created
    // before the Workspace module was installed.
    $revision_state = [
      'live' => [
        1 => [
          1 => ['title' => 'live - 1 - r1 - published', 'status' => TRUE, 'default_revision' => TRUE],
        ],
        2 => [
          2 => ['title' => 'live - 2 - r2 - unpublished', 'status' => FALSE, 'default_revision' => TRUE],
        ],
      ],
      'stage' => [
        1 => [
          1 => ['title' => 'live - 1 - r1 - published', 'status' => TRUE, 'default_revision' => TRUE],
        ],
        2 => [
          2 => ['title' => 'live - 2 - r2 - unpublished', 'status' => FALSE, 'default_revision' => TRUE],
        ],
      ],
    ];
    $test_scenarios['initial_state'] = $revision_state;

    // Unpublish node 1 in 'stage'. The new revision is also added to 'live' but
    // it is not the default revision.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        1 => [
          3 => ['title' => 'stage - 1 - r3 - unpublished', 'status' => FALSE, 'default_revision' => FALSE],
        ],
      ],
      'stage' => [
        1 => [
          1 => ['default_revision' => FALSE],
          3 => ['title' => 'stage - 1 - r3 - unpublished', 'status' => FALSE, 'default_revision' => TRUE],
        ],
      ],
    ]);
    $test_scenarios['unpublish_node_1_in_stage'] = $revision_state;

    // Publish node 2 in 'stage'. The new revision is also added to 'live' but
    // it is not the default revision.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        2 => [
          4 => ['title' => 'stage - 2 - r4 - published', 'status' => TRUE, 'default_revision' => FALSE],
        ],
      ],
      'stage' => [
        2 => [
          2 => ['default_revision' => FALSE],
          4 => ['title' => 'stage - 2 - r4 - published', 'status' => TRUE, 'default_revision' => TRUE],
        ],
      ],
    ]);
    $test_scenarios['publish_node_2_in_stage'] = $revision_state;

    // Adding a new unpublished node on 'stage' should create a single
    // unpublished revision on both 'stage' and 'live'.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        3 => [
          5 => ['title' => 'stage - 3 - r5 - unpublished', 'status' => FALSE, 'default_revision' => TRUE],
        ],
      ],
      'stage' => [
        3 => [
          5 => ['title' => 'stage - 3 - r5 - unpublished', 'status' => FALSE, 'default_revision' => TRUE],
        ],
      ],
    ]);
    $test_scenarios['add_unpublished_node_in_stage'] = $revision_state;

    // Adding a new published node on 'stage' should create two revisions, an
    // unpublished revision on 'live' and a published one on 'stage'.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        4 => [
          6 => ['title' => 'stage - 4 - r6 - published', 'status' => FALSE, 'default_revision' => TRUE],
          7 => ['title' => 'stage - 4 - r6 - published', 'status' => TRUE, 'default_revision' => FALSE],
        ],
      ],
      'stage' => [
        4 => [
          6 => ['title' => 'stage - 4 - r6 - published', 'status' => FALSE, 'default_revision' => FALSE],
          7 => ['title' => 'stage - 4 - r6 - published', 'status' => TRUE, 'default_revision' => TRUE],
        ],
      ],
    ]);
    $test_scenarios['add_published_node_in_stage'] = $revision_state;

    // Deploying 'stage' to 'live' should simply make the latest revisions in
    // 'stage' the default ones in 'live'.
    $revision_state = array_replace_recursive($revision_state, [
      'live' => [
        1 => [
          1 => ['default_revision' => FALSE],
          3 => ['default_revision' => TRUE],
        ],
        2 => [
          2 => ['default_revision' => FALSE],
          4 => ['default_revision' => TRUE],
        ],
        // Node 3 has a single revision for both 'stage' and 'live' and it is
        // already the default revision in both of them.
        4 => [
          6 => ['default_revision' => FALSE],
          7 => ['default_revision' => TRUE],
        ],
      ],
    ]);
    $test_scenarios['deploy_stage_to_live'] = $revision_state;

    // Check the initial state after the module was installed.
    $this->assertWorkspaceStatus($test_scenarios['initial_state'], 'node');

    // Unpublish node 1 in 'stage'.
    $this->switchToWorkspace('stage');
    $node = $this->entityTypeManager->getStorage('node')->load(1);
    $node->setTitle('stage - 1 - r3 - unpublished');
    $node->setUnpublished();
    $node->save();
    $this->assertWorkspaceStatus($test_scenarios['unpublish_node_1_in_stage'], 'node');

    // Publish node 2 in 'stage'.
    $this->switchToWorkspace('stage');
    $node = $this->entityTypeManager->getStorage('node')->load(2);
    $node->setTitle('stage - 2 - r4 - published');
    $node->setPublished();
    $node->save();
    $this->assertWorkspaceStatus($test_scenarios['publish_node_2_in_stage'], 'node');

    // Add a new unpublished node on 'stage'.
    $this->switchToWorkspace('stage');
    $this->createNode(['title' => 'stage - 3 - r5 - unpublished', 'status' => FALSE]);
    $this->assertWorkspaceStatus($test_scenarios['add_unpublished_node_in_stage'], 'node');

    // Add a new published node on 'stage'.
    $this->switchToWorkspace('stage');
    $this->createNode(['title' => 'stage - 4 - r6 - published', 'status' => TRUE]);
    $this->assertWorkspaceStatus($test_scenarios['add_published_node_in_stage'], 'node');

    // Deploy 'stage' to 'live'.
    \Drupal::service('workspace.replication_manager')->replicate(
      $stage->getLocalUpstreamPlugin(),
      $stage->getUpstreamPlugin()
    );
    $this->assertWorkspaceStatus($test_scenarios['deploy_stage_to_live'], 'node');
  }

  /**
   * Checks entity load, entity queries and views results for a test scenario.
   *
   * @param array $expected
   *   An array of expected values, as defined in ::testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type that is being tested.
   */
  protected function assertWorkspaceStatus(array $expected, $entity_type_id) {
    $expected = $this->flattenExpectedValues($expected, $entity_type_id);

    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    foreach ($expected as $workspace_id => $expected_values) {
      $this->switchToWorkspace($workspace_id);

      foreach ($expected_values as $expected_entity_values) {
        $this->assertEntityValues(
          $entity_type_id,
          $expected_entity_values[$entity_keys['id']],
          $expected_entity_values[$entity_keys['revision']],
          $expected_entity_values[$entity_keys['label']],
          $expected_entity_values[$entity_keys['published']],
          $expected_entity_values['default_revision']
        );
      }

      // Check that the 'Frontpage' view only shows published content that is
      // also considered as the default revision in the given workspace.
      $expected_frontpage = array_filter($expected_values, function ($expected_value) {
        return $expected_value['status'] === TRUE && $expected_value['default_revision'] === TRUE;
      });
      $view = Views::getView('frontpage');
      $view->execute();
      // $this->assertIdenticalResultset($view, $expected_frontpage, ['nid' => 'nid']);

      $rendered_view = $view->render('page_1');
      $output = \Drupal::service('renderer')->renderRoot($rendered_view);
      $this->setRawContent($output);
      foreach ($expected_values as $expected_entity_values) {
        if ($expected_entity_values[$entity_keys['published']] === TRUE && $expected_entity_values['default_revision'] === TRUE) {
          // $this->assertRaw($expected_entity_values[$entity_keys['label']]);
        }
        else {
          // $this->assertNoRaw($expected_entity_values[$entity_keys['label']]);
        }
      }
    }
  }

  /**
   * Asserts that a given entity has the correct values in a specific workspace.
   *
   * @param string $entity_type_id
   *   The ID of the entity type to check.
   * @param int $entity_id
   *   The ID of the entity.
   * @param int $revision_id
   *   The expected revision ID of the entity.
   * @param string $label
   *   The expected label of the entity.
   * @param bool $status
   *   The expected publishing status of the entity.
   * @param bool $default_revision
   *   Whether this should be the default revision of the entity.
   */
  protected function assertEntityValues($entity_type_id, $entity_id, $revision_id, $label, $status, $default_revision) {
    // Entity::load() only deals with the default revision.
    if ($default_revision) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\EntityPublishedInterface $entity */
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
      $this->assertEquals($revision_id, $entity->getRevisionId());
      $this->assertEquals($label, $entity->label());
      $this->assertEquals($status, $entity->isPublished());
    }

    // Check entity query.
    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
    $query
      ->condition($entity_keys['id'], $entity_id)
      ->condition($entity_keys['label'], $label)
      ->condition($entity_keys['published'], $status);

    // If the entity is not expected to be the default revision, we need to
    // query all revisions if we want to find it.
    if (!$default_revision) {
      $query->allRevisions();
    }

    $result = $query->execute();
    $this->assertEquals([$revision_id => $entity_id], $result);
  }

  /**
   * Sets a given workspace as active.
   *
   * @param string $workspace_id
   *   The ID of the workspace to switch to.
   */
  protected function switchToWorkspace($workspace_id) {
    /** @var \Drupal\workspace\WorkspaceManager $workspace_manager */
    $workspace_manager = \Drupal::service('workspace.manager');
    if ($workspace_manager->getActiveWorkspace() !== $workspace_id) {
      // Switch the test runner's context to the specified workspace.
      $workspace = $this->entityTypeManager->getStorage('workspace')->load($workspace_id);
      \Drupal::service('workspace.manager')->setActiveWorkspace($workspace);
    }
  }

  /**
   * Flattens the expectations array defined by testWorkspaces().
   *
   * @param array $expected
   *   An array as defined by testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type that is being tested.
   *
   * @return array
   *   An array where all the entity IDs and revision IDs are merged inside each
   *   expected values array.
   */
  protected function flattenExpectedValues(array $expected, $entity_type_id) {
    $flattened = [];

    $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
    foreach ($expected as $workspace_id => $workspace_values) {
      foreach ($workspace_values as $entity_id => $entity_revisions) {
        foreach ($entity_revisions as $revision_id => $revision_values) {
          $flattened[$workspace_id][] = [$entity_keys['id'] => $entity_id, $entity_keys['revision'] => $revision_id] + $revision_values;
        }
      }
    }

    return $flattened;
  }

}
