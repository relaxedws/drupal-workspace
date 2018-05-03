<?php

namespace Drupal\workspace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ReplicationBlockerController extends ControllerBase {

  /**
   * Reset the blocker.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function resetBlocker() {
    $this->state()->set('workspace.last_replication_failed', FALSE);
    drupal_set_message('Replication blocker has been reset you can now create and run deployments.');
    return new RedirectResponse('/admin/reports/status');
  }

}
