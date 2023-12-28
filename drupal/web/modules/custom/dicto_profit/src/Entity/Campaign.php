<?php

namespace Drupal\dicto_profit\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "dicto_profit_campaign",
 *   label = @Translation("Profit Campaign"),
 *   base_table = "profit_campaign",
 *   handlers = {
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "src",
 *   },
 * )
 */
class Campaign extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['profit_id'] = BaseFieldDefinition::create('integer');
    $fields['advertiser_id'] = BaseFieldDefinition::create('integer');
    $fields['original_url'] = BaseFieldDefinition::create('string');
    $fields['referral_link'] = BaseFieldDefinition::create('string');
    $fields['name'] = BaseFieldDefinition::create('string');
    $fields['weight'] = BaseFieldDefinition::create('integer');
    $fields['start_at'] = BaseFieldDefinition::create('timestamp');
    $fields['end_at'] = BaseFieldDefinition::create('timestamp');

    return $fields;
  }

}
