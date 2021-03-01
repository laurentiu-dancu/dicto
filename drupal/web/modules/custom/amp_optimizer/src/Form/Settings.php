<?php

namespace Drupal\amp_optimizer\Form;

use Drupal\amp_optimizer\EventSubscriber\OptimizerSubscriber;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [OptimizerSubscriber::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amp_optimizer_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['transform_enabled'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status of optimisation'),
      '#default_value' => (int) ($this->config(OptimizerSubscriber::CONFIG_NAME)->get('transform_enabled') ?? 0),
      '#options' => [
        1 => $this->t('Enabled'),
        0 => $this->t('Disabled'),
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(OptimizerSubscriber::CONFIG_NAME)
      ->set(
        'transform_enabled',
        (int) $form_state->getValue('transform_enabled', 0)
      )
      ->save();
    parent::submitForm($form, $form_state);
  }

}
