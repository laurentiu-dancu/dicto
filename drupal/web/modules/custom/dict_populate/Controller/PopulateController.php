<?php


namespace Drupal\dict_populate\Controller;


use Drupal\Core\Controller\ControllerBase;

class PopulateController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function populate(): array {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
