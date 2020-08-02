<?php

namespace Drupal\lm_custom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for lm_custom_agreement entity.
 *
 * @ingroup lm_custom
 */
class AgreementListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Lm Custom implements a Agreement model. These agreements are fieldable entities. You can manage the fields on the <a href="@adminlink">Agreements admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('lm_custom.agreement_settings'),
      )),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the agreement list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('AgreementID');
    $header['day_from'] = $this->t('Agreement day from');
    $header['day_to'] = $this->t('Agreement day to');
    $header['agreement_hours'] = $this->t('Agreement hours');
    $header['time_from'] = $this->t('Agreement from time');
    $header['time_to'] = $this->t('Agreement to time');
    $header['sick_start_time'] = $this->t('Agreement sick start time');
    $header['period'] = $this->t('Period');
    $header['ag_status'] = $this->t('Ag status');
    return $header + parent::buildHeader();
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\lm_custom\Entity\Health */
    $row['id'] = $entity->id();
    $row['day_from'] = $entity->day_from->value;
    $row['day_to'] = $entity->day_to->value;
    $row['agreement_hours'] = $entity->agreement_hours->value;
    $row['time_from'] = $entity->time_from->value;
    $row['time_to'] = $entity->time_to->value;
    $row['sick_start_time'] = $entity->sick_start_time->value;
    $row['period'] = $entity->period->value;
    $row['ag_status'] = $entity->ag_status->value;
    return $row + parent::buildRow($entity);
  }

}
?>