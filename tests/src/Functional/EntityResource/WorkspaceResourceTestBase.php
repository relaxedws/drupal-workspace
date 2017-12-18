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
   * The entity ID for the first created entity in testPost().
   *
   * @var string
   *
   * @see ::testPost()
   * @see ::getNormalizedPostEntity()
   */
  protected static $firstCreatedEntityId = 'running_on_faith';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view workspace layla']);
        break;
      case 'POST':
        $this->grantPermissionsToTestedRole(['view workspace layla', 'create workspace']);
        break;
      case 'PATCH':
        $this->grantPermissionsToTestedRole(['view workspace layla', 'update workspace layla']);
        break;
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['view workspace layla', 'delete workspace layla']);
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
  protected function getNormalizedPostEntity() {
    return [
      'id' => [
        [
          'value' => 'running_on_faith',
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
        return "The 'update workspace layla' permission is required.";
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

}
