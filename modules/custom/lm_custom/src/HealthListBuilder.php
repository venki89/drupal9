<?php

namespace Drupal\lm_custom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for lm_custom_health entity.
 *
 * @ingroup lm_custom
 */
class HealthListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Lm Custom implements a Healths model. These healths are fieldable entities. You can manage the fields on the <a href="@adminlink">Healths admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('lm_custom.health_settings'),
      )),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the health list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('HealthID');
    $header['status'] = $this->t('Status');
    $header['sickdates'] = $this->t('Sickdates');
    $header['sickdays'] = $this->t('Sickdays');
    $header['sicktime'] = $this->t('Sicktime');
    $header['part_time_sickhours'] = $this->t('Part time Sickhours');
    $header['full_time_sickhours'] = $this->t('Full time Sickhours');
    $header['approval_status'] = $this->t('Approval Status'); 
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\lm_custom\Entity\Health */
    $row['id'] = $entity->id();
    $row['status'] = $entity->status->value;
    $row['sickdates'] = $entity->sickdates->value;
    $row['sickdays'] = $entity->sickdays->value;
    $row['sicktime'] = $entity->sicktime->value;
    $row['part_time_sickhours'] = $entity->part_time_sickhours->value;
    $row['full_time_sickhours'] = $entity->full_time_sickhours->value;
    $row['approval_status'] = $entity->approval_status->value; 
    return $row + parent::buildRow($entity);
  }

}
?>