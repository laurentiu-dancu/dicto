<?php

namespace Drupal\dicto_views\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dicto_views\Constants;

class DictoViewsDanknessForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'dicto_views_dankness_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['darkness_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Toggle'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
