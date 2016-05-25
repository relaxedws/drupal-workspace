<?php

namespace Drupal\workspace;

/**
 * Defines an interface for Replicator plugins.
 */
interface ReplicatorInterface {

  /**
   * Determine if the replicator applies to the given workspaces.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   *   The workspace to replicate from.
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   *   The workspace to replicate to.
   *
   * @return bool
   *   Returns true if this replicator applies.
   */
  public function applies(WorkspacePointerInterface $source, WorkspacePointerInterface $target);

  /**
   * Perform the replication from the source to target workspace.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   *   The workspace to replicate from.
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   *   The workspace to replicate to.
   *
   * @return array
   *   The array of ??
   *
   * @todo document what the return actually is
   */
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target);

}
