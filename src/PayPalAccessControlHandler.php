<?php

namespace Drupal\bikeclub_report;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the route entity.
 */
class PayPalAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess() is called with the
   * $operation as defined in the routing.yml file.
   */

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Check the admin_permission as defined in your @ContentEntityType
    // annotation.

    $admin_permission = $this->entityType->getAdminPermission();
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view club_paypal entity');
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist. It
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Check the admin_permission as defined in your @ContentEntityType
    // annotation.
    $admin_permission = $this->entityType->getAdminPermission();
    if ($account->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermission($account, 'add club_paypal entity');
  }

}
