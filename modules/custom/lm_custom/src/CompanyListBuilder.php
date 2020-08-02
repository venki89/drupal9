<?php

namespace Drupal\lm_custom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for lm_custom_company entity.
 *
 * @ingroup lm_custom
 */
class CompanyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Lm Custom implements a Company model. These companys are fieldable entities. You can manage the fields on the <a href="@adminlink">Companys admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('lm_custom.company_settings'),
      )),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the company list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('CompanyID');
    $header['company_name'] = $this->t('Company name');
    $header['company_email'] = $this->t('Company email');
    $header['company_phone'] = $this->t('Company phone');
    $header['company_address'] = $this->t('Company address');
    $header['credit_card'] = $this->t('Credit card');
    $header['payment_status'] = $this->t('Payment status');
    $header['company_members'] = $this->t('Company members');
    return $header + parent::buildHeader();
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\lm_custom\Entity\Health */
    $row['id'] = $entity->id();
    $row['company_name'] = $entity->company_name->value;
    $row['company_email'] = $entity->company_email->value;
    $row['company_phone'] = $entity->company_phone->value;
    $row['company_address'] = $entity->company_address->value;
    $row['credit_card'] = $entity->credit_card->value;
    $row['payment_status'] = $entity->payment_status->value;
    $row['company_members'] = $entity->company_members->value;
    return $row + parent::buildRow($entity);
  }

}
?>