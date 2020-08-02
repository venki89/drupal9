<?php

namespace Drupal\lm_custom;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Agreement entity.
 * @ingroup lm_custom
 */
interface AgreementInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>