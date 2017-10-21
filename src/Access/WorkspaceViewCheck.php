<?php

namespace Drupal\workspace\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Class WorkspaceViewCheck
 */
class WorkspaceViewCheck implements AccessInterface {

  /**
   * Checks that the user should be able to view the specified workspace.
   *
   * "View" in practice implies "is allowed to make active".
   *
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   *   The workspace to view.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(WorkspaceInterface $workspace, AccountInterface $account) {
    return AccessResult::allowedIf($workspace->access('view', $account))->addCacheableDependency($workspace);
  }

}
