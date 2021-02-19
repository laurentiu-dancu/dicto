<?php

namespace Drupal\dicto_views;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxy;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\Entity\VoteType;
use Drupal\votingapi\VoteResultFunctionManager;
use Drupal\votingapi_reaction\Plugin\Field\FieldType\VotingApiReactionItemInterface;
use Drupal\votingapi_reaction\VotingApiReactionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Manages reactions through Voting API entities.
 */
class ReactionManager extends VotingApiReactionManager {
  /**
   * Return rendered list of active reactions.
   *
   * @param array $settings
   *   Field settings.
   * @param array $results
   *   Array containing Voting API voting results.
   *
   * @return array
   *   Rendered reactions.
   */
  public function getReactions(array $settings, array $results) {
    // Get only enabled reactions.
    $entities = array_filter($this->allReactions(), function (VoteType $entity) use ($settings) {
      return in_array($entity->id(), array_filter($settings['reactions']));
    });

    // Configure the object.
    $reactions = array_map(function (VoteType $entity) use ($settings, $results) {
      $reaction = [
        '#theme' => 'votingapi_reaction_item',
        '#reaction' => $entity->id(),
      ];

      if ($settings['show_label']) {
        $reaction['#label'] = $entity->label();
      }

      if ($settings['show_count']) {
        $reaction['#count'] = isset($results[$entity->id()]['vote_sum'])
          ? $results[$entity->id()]['vote_sum']
          : 0;
      }

      return $reaction;
    }, $entities);

    $reactions = array_reverse($reactions);

    // Render reactions.
    return array_map(function ($reaction) {
      return $this->renderer->render($reaction);
    }, $reactions);
  }
}
