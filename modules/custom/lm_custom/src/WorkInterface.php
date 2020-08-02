<?php

namespace Drupal\lm_custom;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Work entity.
 * @ingroup lm_custom
 */
interface WorkInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>