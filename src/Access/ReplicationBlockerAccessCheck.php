<?php

namespace Drupal\workspace\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Access check for replication blocker routes.
 */
class ReplicationBlockerAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param string $key
   *   The replication blocker key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($key) {
    if ($key != \Drupal::state()->get('workspace.replication_blocker_key')) {
      \Drupal::logger('workspace')->notice('Replication blocker could not be reset because an invalid key was used.');
      return AccessResult::forbidden()->setCacheMaxAge(0);
    }
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}
