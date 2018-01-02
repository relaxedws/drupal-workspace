<?php

namespace Drupal\Tests\workspace\Functional\EntityResource;

use Drupal\Tests\rest\Functional\BcTimestampNormalizerUnixTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Drupal\user\Entity\User;
use Drupal\workspace\Entity\Workspace;

/**
 * Base class for workspace EntityResource tests.
 */
abstract class WorkspaceResourceTestBase extends EntityResourceTestBase {

  use BcTimestampNormalizerUnixTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workspace'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'workspace';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = ['changed'];

  /**
   * {@inheritdoc}
   */
  protected static $firstCreatedEntityId = 'running_on_faith';

  /**
   * {@inheritdoc}
   */
  protected static $secondCreatedEntityId = 'running_on_faith_1';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view workspace layla']);
        break;
      case 'POST':
        $this->grantPermissionsToTestedRole(['create workspace']);
        break;
      case 'PATCH':
        $this->grantPermissionsToTestedRole(['edit workspace layla']);
        break;
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete workspace layla']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $workspace = Workspace::create([
      'id' => 'layla',
      'label' => 'Layla',
      'upstream' => 'local_workspace:live',
    ]);
    $workspace->save();
    return $workspace;
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity() {
    $workspace = $this->entity->createDuplicate();
    $workspace->id = 'layla_dupe';
    $workspace->label = 'Layla_dupe';
    $workspace->save();
    return $workspace;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $author = User::load($this->entity->getOwnerId());
    return [
      'created' => [
        $this->formatExpectedTimestampItemValues((int) $this->entity->getStartTime()),
      ],
      'changed' => [
        $this->formatExpectedTimestampItemValues($this->entity->getChangedTime()),
      ],
      'id' => [
        [
          'value' => 'layla',
        ],
      ],
      'label' => [
        [
          'value' => 'Layla',
        ],
      ],
      'revision_id' => [
        [
          'value' => 3,
        ],
      ],
      'uid' => [
        [
          'target_id' => (int) $author->id(),
          'target_type' => 'user',
          'target_uuid' => $author->uuid(),
          'url' => base_path() . 'user/' . $author->id(),
        ],
      ],
      'upstream' => [
        [
          'value' => 'local_workspace:live',
        ],
      ],
      'uuid' => [
        [
          'value' => $this->entity->uuid()
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity($which = NULL) {
    return [
      'id' => [
        [
          'value' => 'running_on_faith' . ($which === 1 ? '_1' : ''),
        ],
      ],
      'label' => [
        [
          'value' => 'Running on faith',
        ],
      ],
      'upstream' => [
        [
          'value' => 'local_workspace:stage',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPatchEntity() {
    return [
      'label' => [
        [
          'value' => 'Running on faith',
        ],
      ],
      'upstream' => [
        [
          'value' => 'local_workspace:stage',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'GET':
        return "The 'view workspace layla' permission is required.";
        break;
      case 'POST':
        return "The 'create workspace' permission is required.";
        break;
      case 'PATCH':
        return "The 'edit workspace layla' permission is required.";
        break;
      case 'DELETE':
        return "The 'delete workspace layla' permission is required.";
        break;
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    return parent::getExpectedUnauthorizedAccessCacheability()->addCacheTags($this->entity->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function testDelete() {
    // @todo Workspaces can not yet be deleted.
  }

}
