<?php


namespace Drupal\dicto_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * @MigrateSource(
 *   id="dicto:source:users",
 *   key="default"
 * )
 */
class SqlUserSource extends SqlBase {

  /**
   * @inheritDoc
   */
  public function fields(): array {
    return [
      'id' => 'Original ID',
      'name' => 'Name',
      'password' => 'Password',
      'mail' => 'Mail',
      'status' => 'Status',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getIds(): array {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'a',
      ],
    ];
  }

  /**
   * @inheritDoc
   */
  public function query(): SelectInterface {
    return $this->select('a_author', 'a')
      ->fields('a', ['id', 'name']);
  }

  /**
   * @inheritDoc
   */
  public function prepareRow(Row $row): bool {
    $row->setSourceProperty('password', random_bytes(32));
    $row->setSourceProperty('mail', random_int(1000000000, 9999999999) . '@example.com');
    $row->setSourceProperty('status', 1);
    return parent::prepareRow($row);
  }

}
