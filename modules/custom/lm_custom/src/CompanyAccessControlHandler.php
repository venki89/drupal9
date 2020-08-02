<?php

namespace Drupal\lm_custom;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the company entity.
 *
 * @see \Drupal\lm_custom\Entity\Company.
 */
class CompanyAccessControlHandler extends EntityAccessControlHandler {
  
  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view company entity');
        
      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit company entity');
        
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete company entity');
    }
    return AccessResult::allowed();
  }
  
  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add company entity');
  }
  
}
?>