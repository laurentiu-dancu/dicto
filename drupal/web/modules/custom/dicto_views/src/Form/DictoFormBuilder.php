<?php


namespace Drupal\dicto_views\Form;


use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;

class DictoFormBuilder extends FormBuilder {
  protected function elementTriggeredScriptedSubmission($element, FormStateInterface &$form_state) {
    return parent::elementTriggeredScriptedSubmission($element, $form_state);

    $input = $form_state->getUserInput();
    if (!empty($input['_triggering_element_name']) && $element['#name'] == $input['_triggering_element_name']) {
      if (!\Drupal::request()->query->get('__amp_source_origin')) {
        return FALSE;
      }
      if (empty($input['_triggering_element_value']) || $input['_triggering_element_value'] == $element['#value']) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
