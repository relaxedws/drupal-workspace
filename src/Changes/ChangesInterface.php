<?php

namespace Drupal\workspace\Changes;

/**
 * Builds a list of changes between two workspaces.
 */
interface ChangesInterface {

  /**
   * Sets from what sequence number to check for changes.
   *
   * @param int $sequence_id
   *   The sequence ID to start including changes from.
   *
   * @return $this
   */
  public function setLastSequenceId($sequence_id);

  /**
   * Return an array of Change value objects.
   *
   * @return \Drupal\workspace\Changes\Change[]
   *   An array of Change objects.
   */
  public function getChanges();

}
