<?php

namespace Drupal\workspace\Changes;

/**
 * Define and build a list of changes for a Workspace.
 */
interface ChangesInterface {

  /**
   * Set the flag for including entities in the list of changes.
   *
   * @param bool $include_entities
   *   Whether to include entities in the list of changes.
   *
   * @return \Drupal\workspace\Changes\ChangesInterface
   *   Returns $this.
   */
  public function includeEntities($include_entities);

  /**
   * Sets from what sequence number to check for changes.
   *
   * @param int $seq
   *   The sequence ID to start including changes from. Result includes $seq.
   *
   * @return \Drupal\workspace\Changes\ChangesInterface
   *   Returns $this.
   */
  public function lastSeq($seq);

  /**
   * Return the changes in a 'normal' way.
   *
   * @return array
   */
  public function getNormal();

  /**
   * Return the changes with a 'longpoll'.
   *
   * @return array
   */
  public function getLongpoll();

}
