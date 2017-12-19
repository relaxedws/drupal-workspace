<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for the Replication log entity type.
 */
interface ReplicationLogInterface extends ContentEntityInterface {

  /**
   * Gets the entire replication history.
   *
   * @return array
   *   List of history values.
   */
  public function getHistory();

  /**
   * Adds a new set of data to the replication history.
   *
   * @param array $history
   *   An array of values for the 'history' field.
   *
   * @return $this
   */
  public function addHistory(array $history);

  /**
   * Gets the session ID.
   *
   * @return string
   *   The session ID.
   */
  public function getSessionId();

  /**
   * Sets the session ID.
   *
   * @param string $session_id
   *   The session ID to set.
   *
   * @return $this
   */
  public function setSessionId($session_id);

  /**
   * Gets the last processed checkpoint.
   *
   * @return string
   *   The last processed checkpoint.
   */
  public function getSourceLastSequence();

  /**
   * Sets the last processed checkpoint.
   *
   * @param string $source_last_sequence
   *   The last processed checkpoint.
   *
   * @return $this
   */
  public function setSourceLastSequence($source_last_sequence);

  /**
   * Loads an existing replication log or creates one if necessary.
   *
   * @param string $id
   *   The ID of the replication log entity.
   *
   * @return $this
   */
  public static function loadOrCreate($id);

}
