<?php

namespace Drupal\workspace;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the workspace entity type.
 *
 * @see \Drupal\workspace\Entity\Workspace
 */
class WorkspaceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\workspace\Entity\WorkspaceInterface $entity */
    $operations = [
      'view' => ['any' => 'view any workspace', 'own' => 'view own workspace'],
      'update' => ['any' => 'edit any workspace', 'own' => 'edit own workspace'],
      'delete' => ['any' => 'delete any workspace', 'own' => 'delete own workspace'],
    ];

    // The default workspace is always viewable, no matter what.
    $result = AccessResult::allowedIf($operation == 'view' && $entity->id() == WorkspaceManager::DEFAULT_WORKSPACE)->addCacheableDependency($entity)
      // Or if the user has permission to access any workspace at all.
      ->orIf(AccessResult::allowedIfHasPermission($account, $operations[$operation]['any']))
      // Or if it's their own workspace, and they have permission to access
      // their own workspace.
      ->orIf(
        AccessResult::allowedIf($entity->getOwnerId() == $account->id())->addCacheableDependency($entity)
          ->andIf(AccessResult::allowedIfHasPermission($account, $operations[$operation]['own']))
      )
      ->orIf(AccessResult::allowedIfHasPermission($account, $operation . '_workspace_' . $entity->id()));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create workspace');
  }

}
