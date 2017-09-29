<?php

namespace Drupal\workspace;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * An upstream is a source or a target for replication. For example it could be
 * a workspace, a remote site, a non-drupal application or a database such as
 * CouchDB.
 *
 * When a replication happens the replicator will determine if it should run
 * based on the source and target upstream plugins. Then the replication will
 * use data from the upstream plugins to perform the replication. For example an
 * internal replication might just need the workspace IDs, but a contrib module
 * performing an external replication may need host name, port, username,
 * password etc.
 */
interface UpstreamInterface extends PluginInspectionInterface {

  /**
   * Returns the label of the upstream. This is used as a form label where a user
   * selects the target for replicating to.
   *
   * @return string
   *   The label of the upstream.
   */
  public function getLabel();

}
