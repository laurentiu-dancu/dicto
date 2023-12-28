<?php

namespace Drupal\dicto_profit\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "dicto_profit_campaign_image",
 *   label = @Translation("Profit Campaign Image"),
 *   base_table = "profit_campaign_image",
 *   handlers = {
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "src",
 *   },
 * )
 */
class CampaignImage extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['campaign'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'dicto_profit_campaign');
    $fields['src'] = BaseFieldDefinition::create('string');
    $fields['bucket'] = BaseFieldDefinition::create('string');
    $fields['width'] = BaseFieldDefinition::create('integer');
    $fields['height'] = BaseFieldDefinition::create('integer');

    return $fields;
  }

}
