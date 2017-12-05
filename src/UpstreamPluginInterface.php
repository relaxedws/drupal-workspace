<?php

namespace Drupal\workspace;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Upstream plugins represent the source / destination of a content replication.
 *
 * An upstream can be either a workspace, a remote site, a non-Drupal
 * application or a database such as CouchDB.
 *
 * When a replication happens the replicator will determine if it should run
 * based on the source and target upstream plugins. Then the replication will
 * use data from the upstream plugins to perform the replication. For example an
 * internal replication might just need the workspace IDs, but a contrib module
 * performing an external replication may need hostname, port, username,
 * password etc.
 */
interface UpstreamPluginInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Default empty value for upstream fields.
   */
  const UPSTREAM_FIELD_EMPTY = '_none';

  /**
   * Returns the label of the upstream.
   *
   * This is used as a form label where a user selects the replication target.
   *
   * @return string
   *   The label text, which could be a plain string or an object that can be
   *   cast to a string.
   */
  public function getLabel();

  /**
   * Returns the upstream plugin description.
   *
   * @return string
   *   The description text, which could be a plain string or an object that can
   *   be cast to a string.
   */
  public function getDescription();

  /**
   * Returns whether the upstream is remote or not.
   *
   * @return bool
   *   TRUE if the upstream is remote, FALSE otherwise.
   */
  public function isRemote();

}
