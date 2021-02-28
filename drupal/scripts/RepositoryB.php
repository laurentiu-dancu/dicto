<?php

require_once 'Repository.php';

class RepositoryB extends Repository {

  const SERVER_NAME = 'mysql';

  const USERNAME = 'root';

  const PASSWORD = 'root';

  const DATABASE = 'drupal2';

  public function createDefinition(string $term, array $definition, int $authorId): ?int {
    global $slugify;
    try {
      $stmt = $this->connection->prepare('insert into a_definition set term = ?, example = ?, def = ?, author_id = ?, score_up = ?, score_down = ?, createdAt = ?, slug = ?');
      $this->connection->beginTransaction();
      $slug = $slugify->slugify($term);
      $date = DateTime::createFromFormat('d M Y H:i', $definition['createdAt'])->format('Y-m-d');
      $stmt->execute([$term, $definition['example'], $definition['definition'], $authorId, $definition['scoreUp'], $definition['scoreDown'], $date, $slug]);
      $id = (int)$this->connection->lastInsertId();
      $this->connection->commit();
      return $id;
    } catch (PDOException $e) {
      $this->connection->rollBack();
      echo 'Failed to add definition for term ' . $term . "\r\n";
      return null;
    }
  }
}
