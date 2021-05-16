<?php

namespace Drupal\dicto_pixie\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "dicto_pixie_page_access_log",
 *   label = @Translation("Page Access Log"),
 *   base_table = "page_access_log",
 *   handlers = {
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "page",
 *   },
 * )
 */
class PageAccessLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['page'] = BaseFieldDefinition::create('string');
    $fields['ipHash'] = BaseFieldDefinition::create('string');
    $fields['country'] = BaseFieldDefinition::create('string');
    $fields['agent'] = BaseFieldDefinition::create('string');
    $fields['created'] = BaseFieldDefinition::create('created');

    return $fields;
  }

}
