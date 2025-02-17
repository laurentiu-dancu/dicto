<?php

namespace Drupal\dicto_views\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi_reaction\Form\VotingApiReactionForm;
use Drupal\votingapi_reaction\Plugin\Field\FieldType\VotingApiReactionItemInterface;
use Drupal\votingapi_reaction\VotingApiReactionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form implementation of the reaction form used in field formatter.
 */
class DictoVotingApiReactionForm extends VotingApiReactionForm {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    /* @var \Drupal\votingapi\Entity\Vote $entity */
    $entity = $this->getEntity();

    return implode('_', [
      'votingapi_reaction',
      $entity->getVotedEntityType(),
      $entity->getVotedEntityId(),
      $entity->get('field_name')->value,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prepare settings.
    $field_items = $form_state->get('field_items');
    $settings = $form_state->get('formatter_settings') + $field_items->getSettings();

    // Check form status and user access to the form.
    $form = EntityForm::buildForm($form, $form_state);
    $form['#id'] = Html::getUniqueId('votingapi_reaction_form');
    $form['#attached']['library'][] = 'votingapi_reaction/scripts';
    $form['#attributes']['class'][] = 'votingapi-reaction-form';
    $form['#attributes']['autocomplete'] = 'off';
    $form['#cache']['max-age'] = 0;

    // Check user access and form status.
    $status = $this->checkStatus($form_state);
    $access = $this->checkAccess($form_state, $this->entity);
    $form['#access'] = !is_null($status) && !is_null($access);

    // Display summary.
    $results = $this->reactionManager->getResults($this->entity, $settings);
    if ($settings['show_summary']) {
      $total = array_sum(array_column($results, 'vote_sum'));
      $form['summary'] = [
        '#type' => '#markup',
        '#markup' => $this->formatPlural($total, '@count reaction', '@count reactions'),
      ];
    }

    // Display reactions.
    $form['type'] = [
      '#type' => 'radios',
      '#options' => $this->reactionManager->getReactionsPoly($settings, $results, $form['#id']),
      '#default_value' => $this->entity->bundle(),
      '#id' => $form['#id'] . '-vote',
      '#ajax' => [
        'callback' => [$this, 'ajaxSubmitForm'],
        'event' => 'click',
        'wrapper' => $form['#id'],
        'progress' => ['type' => NULL, 'message' => NULL],
      ],
      '#disabled' => !$status || !$access,
    ];

    // Store reactions order, so we can persist it for AJAX.
    $form['reactions'] = [
      '#type' => 'value',
      '#value' => $form_state->hasValue('reactions') ? $form_state->getValue('reactions') : array_keys($form['type']['#options']),
    ];

    // Re-purpose entity submit button.
    $form['actions'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#noscript' => TRUE,
      '#weight' => 1,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#disabled' => !$status || !$access,
      '#submit' => ['::submitForm'],
    ];
    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#disabled' => !$status || !$access,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    // Prepare settings.
    $field_items = $form_state->get('field_items');
    $settings = $form_state->get('formatter_settings') + $field_items->getSettings();

    // Try to get the last reaction.
    if ($entity = $this->reactionManager->lastReaction($this->entity, $settings)) {
      $this->entity = $entity;
    }
    // If no reaction found, create a new one.
    else {
      $this->entity = Vote::create([
        'type' => 'vote',
        'entity_id' => $this->entity->getVotedEntityId(),
        'entity_type' => $this->entity->getVotedEntityType(),
        'value_type' => 'option',
        'value' => 1,
        'field_name' => $this->entity->get('field_name')->value,
      ]);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If new reaction was selected.
    $reaction = NULL;
    $newVote = true;
    $trigger = $form_state->getTriggeringElement();
    if ($this->entity->bundle() != $form_state->getValue('type')
      && array_search('reset', $trigger['#parents']) === FALSE) {
      ContentEntityForm::submitForm($form, $form_state);
      parent::save($form, $form_state);

      $reaction = $form_state->getValue('type');

      if ($this->currentUser->isAuthenticated()) {
        $this->reactionManager->rememberReaction($this->entity);
      }
    }
    // If same reaction was selected.
    else {
      $newVote = false;
      // noop;
    }

    // Recalculate results for last reaction.
    $this->reactionManager->recalculateResults(
      $this->entity->getVotedEntityType(),
      $this->entity->getVotedEntityId(),
      $this->entity->bundle()
    );

    // Recalculate results for new reaction.
    $this->reactionManager->recalculateResults(
      $this->entity->getVotedEntityType(),
      $this->entity->getVotedEntityId(),
      $reaction
    );

    // Prepare settings.
    $field_items = $form_state->get('field_items');
    $settings = $form_state->get('formatter_settings') + $field_items->getSettings();
    $results = $this->reactionManager->getResults($this->entity, $settings);
    $form['results'] = $results;
    if ($newVote) {
      $form['results']['message'] = "<span><strong>Mulțumim pentru vot!</strong></span></br>".
        "<span>Votul tău a fost înregistrat și o să apară public în max. 24 de ore.</span>";
    } else {
      $form['results']['message'] = "<span><strong>Ai votat deja!</strong></span></br>".
        "<span>Votul tău nu a fost înregistrat pentru că ai mai votat definiția asta. Încearcă eventual celălalt buton.</span>";
    }

    // Update summary.
    if (isset($form['summary'])) {
      $form['summary']['#markup'] = $this->formatPlural(
        array_sum(array_column($results, 'vote_sum')),
        '@count reaction',
        '@count reactions'
      );
    }

    // Update reactions.
    $form['type']['#options'] = $this->reactionManager->getReactionsPoly($settings, $results, $form['#id']);
    foreach ($form['reactions']['#value'] as $weight => $id) {
      $form['type'][$id]['#title'] = $form['type']['#options'][$id];
      $form['type'][$id]['#value'] = $reaction;
      $form['type'][$id]['#weight'] = $weight / 100;
    }
  }

  /**
   * Ajax submit handler.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Return form.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {
    $this->submitForm($form, $form_state);

    return $form;
  }

  /**
   * Check if current form should be visible, closed or opened.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   Null if form should be hidden.
   *   Boolean if form is disabled or enabled.
   */
  private function checkStatus(FormStateInterface $form_state) {
    $items = $form_state->get('field_items');

    if ($items->status == VotingApiReactionItemInterface::HIDDEN) {
      return NULL;
    }

    return $items->status == VotingApiReactionItemInterface::OPEN;
  }

  /**
   * Check if current user has access to this form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed $modify
   *   Vote entity is beign modified.
   *
   * @return mixed
   *   Null if user any access.
   *   Boolean if user can create or modify.
   */
  private function checkAccess(FormStateInterface $form_state, $modify = FALSE) {
    $items = $form_state->get('field_items');
    $instance = implode(':', [
      $items->getEntity()->getEntityTypeId(),
      $items->getEntity()->bundle(),
      $items->getName(),
    ]);

    if (!$this->currentUser->hasPermission('view reactions on ' . $instance)) {
      return NULL;
    }

    return $this->currentUser->hasPermission($modify
      ? 'modify reaction on ' . $instance
      : 'create reaction on ' . $instance
    );
  }

}
