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
  public function getReactionsPoly(array $settings, array $results, string $formId) {
    // Get only enabled reactions.
    $entities = array_filter($this->allReactions(), function (VoteType $entity) use ($settings) {
      return in_array($entity->id(), array_filter($settings['reactions']));
    });

    // Configure the object.
    $reactions = array_map(function (VoteType $entity) use ($formId, $settings, $results) {
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
      $reaction['#icon'] = str_replace('-', '_',$formId);

      return $reaction;
    }, $entities);

    $reactions = array_reverse($reactions);

    // Render reactions.
    return array_map(function ($reaction) {
      return $this->renderer->render($reaction);
    }, $reactions);
  }

  /**
   * Load previous reaction of the user for certain field.
   *
   * @param \Drupal\votingapi\Entity\Vote $entity
   *   Current vote entity.
   * @param array $settings
   *   Field settings.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Last reaction for current user.
   */
  public function lastReaction(Vote $entity, array $settings) {
    $query = $this->voteStorage->getQuery()
      ->condition('entity_id', $entity->getVotedEntityId())
      ->condition('entity_type', $entity->getVotedEntityType())
      ->condition('field_name', $entity->get('field_name')->value)
      ->condition('user_id', $this->currentUser->id());

    if ($this->currentUser->isAnonymous()) {
      // Filter by IP method.
      if (in_array(VotingApiReactionItemInterface::BY_IP, $settings['anonymous_detection'])) {
        $ip = Vote::getCurrentIp();
        $query->condition('vote_source', Vote::getCurrentIp());
      }

      // Filter by rollover.
      $rollover = $settings['anonymous_rollover'];
      if ($rollover == VotingApiReactionItemInterface::VOTINGAPI_ROLLOVER) {
        $rollover = $this->configFactory
          ->get('votingapi.settings')
          ->get('anonymous_window');
      }
      if ($rollover != VotingApiReactionItemInterface::NEVER_ROLLOVER) {
        $query->condition('timestamp', time() - $rollover, '>=');
      }
    }

    $ids = $query->execute();

    return $this->voteStorage->load(intval(array_pop($ids)));
  }
}
