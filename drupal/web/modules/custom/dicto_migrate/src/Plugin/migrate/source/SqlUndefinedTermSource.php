<?php

namespace Drupal\dicto_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * @MigrateSource(
 *   id="dicto:source:undefined_terms",
 *   key="default"
 * )
 */
class SqlUndefinedTermSource extends SqlBase implements MigrateSourceInterface {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $fields = [
      'id',
      'term',
    ];
    $query = $this->select('a_undefined_term', 'd')
      ->fields('d', $fields);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => 'Original ID',
      'term' => 'Term name',
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'd',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $select = $this->select('a_undefined_term', 'ut')
      ->fields('nfd', ['nid'])
      ->condition('ut.term', $row->getSourceProperty('term'));
    $select->innerJoin('unique_related_term', 'urt', 'ut.id = urt.undefined_term_id');
    $select->leftJoin('node_field_data', 'nfd', 'urt.term = nfd.title');
    $select->innerJoin('node__field_definitie', 'fd', 'nfd.nid = fd.entity_id and fd.field_definitie_value = urt.def');
    $nodes = $select->execute()->fetchCol();

    $select = $this->select('a_undefined_term', 'ut')
      ->distinct()
      ->fields('urt', ['term', 'def'])
      ->condition('ut.term', $row->getSourceProperty('term'))
      ->condition('nid', NULL, 'is')
      ->condition('urt.def', '', '!=');
    $select->innerJoin('unique_related_term', 'urt', 'ut.id = urt.undefined_term_id');
    $select->leftJoin('node_field_data', 'nfd', 'urt.term = nfd.title');
    $select->leftJoin('node__field_definitie', 'fd', 'nfd.nid = fd.entity_id and fd.field_definitie_value = urt.def');
    $new = $select->execute()->fetchAll();
    foreach ($new as $item) {
      $node = Node::create([
        'type' => 'definition',
        'title' => $item['term'],
        'field_definitie' => $item['def'],
        'field_exemplu' => '',
      ]);
      $nodes[] = $node->save();
    }

    $row->setSourceProperty('nodes', $nodes);

    return parent::prepareRow($row);
  }

}
