<?php

namespace Drupal\mata;

use Drupal\node\Entity\Node;

class DeleteMata {
  public static function deleteMataExample($nids, &$context){
    $message = 'Deleting Mata...';
    $results = array();
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $results[] = $node->delete();
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  function deleteMataExampleFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One post processed.', '@count posts processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
  }
}
