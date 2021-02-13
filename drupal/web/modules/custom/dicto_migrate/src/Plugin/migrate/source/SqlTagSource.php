<?php

namespace Drupal\dicto_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * @MigrateSource(
 *   id="dicto:source:tags",
 *   key="default"
 * )
 */
class SqlTagSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('a_tag', 't')
      ->fields('t', ['id', 'name']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => 'Original ID',
      'name' => $this->t('Tag name'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 't',
      ],
    ];
  }

}
