<?php

namespace Drupal\mata\Controller;

use Drupal\Core\Controller\ControllerBase;

require_once '/var/www/html/scripts/Repository.php';

/**
 * Class MataController.
 */
class MataController extends ControllerBase {

  /**
   * Mata.
   *
   * @return string
   *   Return Hello string.
   */
  public function mata() {
    $repo = new \Repository();
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'definition')
      ->sort('created', 'ASC')
      ->execute();

    $batch = array(
      'title' => t('Deleting Mata...'),
      'operations' => array(
        array(
          '\Drupal\mata\DeleteMata::deleteMataExample',
          array($nids)
        ),
      ),
      'finished' => '\Drupal\mata\DeleteMata::deleteMataExampleFinishedCallback',
    );

    batch_set($batch);
    return [];
  }

}
