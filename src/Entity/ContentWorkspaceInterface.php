<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * An interface for Content workspace entity.
 *
 * Content workspace entities track the workspace of other content entities.
 */
interface ContentWorkspaceInterface extends ContentEntityInterface, EntityOwnerInterface, EntityPublishedInterface {

}
