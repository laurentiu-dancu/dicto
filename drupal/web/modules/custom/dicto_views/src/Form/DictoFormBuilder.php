<?php


namespace Drupal\dicto_views\Form;


use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\Exception\BrokenPostRequestException;
use Drupal\Core\Form\FormAjaxException;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;

class DictoFormBuilder extends FormBuilder {
  protected function elementTriggeredScriptedSubmission($element, FormStateInterface &$form_state) {
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

  /**
   * {@inheritdoc}
   */
  public function buildForm($form_arg, FormStateInterface &$form_state) {
    // Ensure the form ID is prepared.
    $form_id = $this->getFormId($form_arg, $form_state);

    $request = $this->requestStack->getCurrentRequest();

    // Inform $form_state about the request method that's building it, so that
    // it can prevent persisting state changes during HTTP methods for which
    // that is disallowed by HTTP: GET and HEAD.
    $form_state->setRequestMethod($request->getMethod());

    $input = $form_state->getUserInput();
    if (!isset($input)) {
      $input = $form_state->isMethodType('get') ? $request->query->all() : $request->request->all();
      $form_state->setUserInput($input);
    }

    if (isset($_SESSION['batch_form_state'])) {
      $form_state = $_SESSION['batch_form_state'];
      unset($_SESSION['batch_form_state']);
      return $this->rebuildForm($form_id, $form_state);
    }

    $check_cache = isset($input['form_id']) && $input['form_id'] == $form_id && !empty($input['form_build_id']);
    if ($check_cache) {
      $form = $this->getCache($input['form_build_id'], $form_state);
    }

    if (!isset($form)) {
      if ($check_cache) {
        $form_state_before_retrieval = clone $form_state;
      }

      $form = $this->retrieveForm($form_id, $form_state);
      $this->prepareForm($form_id, $form, $form_state);

      if ($check_cache) {
        $cache_form_state = $form_state->getCacheableArray();
        $cache_form_state['always_process'] = $form_state->getAlwaysProcess();
        $cache_form_state['temporary'] = $form_state->getTemporary();
        $form_state = $form_state_before_retrieval;
        $form_state->setFormState($cache_form_state);
      }
    }

    // If this form is an AJAX request, disable all form redirects.
    $request = $this->requestStack->getCurrentRequest();
    if ($ajax_form_request = $request->query->has('__amp_source_origin')) {
      $form_state->disableRedirect();
    }

    $response = $this->processForm($form_id, $form, $form_state);


    if ($ajax_form_request && !$request->request->has('form_id')) {
      throw new BrokenPostRequestException($this->getFileUploadMaxSize());
    }

    if ($ajax_form_request && $form_state->isProcessingInput() && $request->request->get('form_id') == $form_id) {
      throw new FormAjaxException($form, $form_state);
    }

    if ($response instanceof Response) {
      throw new EnforcedResponseException($response);
    }


    return $form;
  }
}
