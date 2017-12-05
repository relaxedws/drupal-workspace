<?php

namespace Drupal\workspace\EntityQuery;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * Alters entity queries to use a workspace revision instead of the default one.
 */
class Query extends BaseQuery {

  use QueryTrait;

}
