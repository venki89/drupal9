<?php

namespace Drupal\lm_custom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for lm_custom_work entity.
 *
 * @ingroup lm_custom
 */
class WorkListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Lm Custom implements a Work model. These works are fieldable entities. You can manage the fields on the <a href="@adminlink">Works admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('lm_custom.work_settings'),
      )),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the work list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('WorkID');
    $header['workdays'] = $this->t('Workdays');
    $header['workhours'] = $this->t('Workhours');
    $header['work_start_time'] = $this->t('Work start time');
    $header['sick_start_time'] = $this->t('Sick start time');
    return $header + parent::buildHeader();
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\lm_custom\Entity\Health */
    $row['id'] = $entity->id();
    $row['workdays'] = $entity->workdays->value;
    $row['workhours'] = $entity->workhours->value;
    $row['work_start_time'] = $entity->work_start_time->value;
    $row['sick_start_time'] = $entity->sick_start_time->value;
    return $row + parent::buildRow($entity);
  }

}
?>