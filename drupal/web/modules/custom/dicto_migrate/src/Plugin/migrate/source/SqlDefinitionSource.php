<?php

namespace Drupal\dicto_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * @MigrateSource(
 *   id="dicto:source:definitions",
 *   key="default"
 * )
 */
class SqlDefinitionSource extends SqlBase implements MigrateSourceInterface {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $fields = [
      'original_id',
      'term',
      'example',
      'def',
      'author_id',
      'score_up',
      'score_down',
      'createdAt',
    ];
    $query = $this->select('unique_definition', 'd')
      ->fields('d', $fields);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'original_id' => 'Original ID',
      'term' => 'Term name',
      'example' => 'Example',
      'def' => 'Definition',
      'author_id' => 'Original Author ID',
      'score_up' => 'Up-score',
      'score_down' => 'Down-score',
      'createdAt' => 'Created Date',
      'tags' => 'Tags',
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'original_id' => [
        'type' => 'integer',
        'alias' => 'd',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $select = $this->select('a_tag_definition', 'td')
      ->fields('t', ['name'])
      ->condition('definition_id', $row->getSourceProperty('original_id'));
    $select->innerJoin('a_tag', 't', 'td.tag_id = t.id');
    $tags = $select->execute()
      ->fetchCol();
    $row->setSourceProperty('tags', $tags);

    return parent::prepareRow($row);
  }

}
