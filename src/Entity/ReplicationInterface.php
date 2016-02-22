<?php

/**
 * @file
 * Contains \Drupal\workspace\Entity\ReplicationInterface.
 */

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Replication entities.
 *
 * @ingroup workspace
 */
interface ReplicationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
