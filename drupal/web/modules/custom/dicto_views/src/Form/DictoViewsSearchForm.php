<?php

namespace Drupal\dicto_views\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dicto_views\Constants;

class DictoViewsSearchForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'dicto_views_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['term'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
      '#autocomplete_route_name' => 'dicto_views.autocomplete.definitions',
      '#attributes' => [
        'title' => $this->t('Caută un cuvânt...'),
        'placeholder' => 'Caută un cuvânt...',
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('🔎'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $slugify = new \Cocur\Slugify\Slugify(['regexp' => Constants::SLUG_REGEX]);
    $slug = $slugify->slugify($form_state->getValue('term'));
    if ($slug) {
      $form_state->setRedirect('view.term.term_page', ['arg_0' => $slug]);
    }
  }
}
