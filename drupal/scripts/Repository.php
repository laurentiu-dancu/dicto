<?php

class Repository {

  const SERVER_NAME = 'mysql';

  const USERNAME = 'root';

  const PASSWORD = 'root';

  const DATABASE = 'drupal2';

  protected PDO $connection;

  public function __construct(
    protected array $tagMap = [],
    protected array $authorMap = [],
    protected array $undefinedMap = []
  ) {
    $conString = sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", self::SERVER_NAME, self::DATABASE);
    $this->connection = new PDO($conString, self::USERNAME, self::PASSWORD);
    $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  public function createTag(string $tagName, string $slug): ?int {
    try {
      $stmt = $this->connection->prepare('insert into a_tag set name = ?, slug = ?');
      $this->connection->beginTransaction();
      $stmt->execute([$tagName, $slug]);
      $id = (int)$this->connection->lastInsertId();
      $this->connection->commit();
      return $id;
    } catch (PDOException $e) {
      $this->connection->rollBack();
      $result = $this->connection->prepare("select id from a_tag where name = :tagName");
      $result->bindParam(':tagName', $tagName);
      $result->execute();
      return $result?->fetchColumn() ?? null;
    }
  }

  public function createAuthor(string $authorName): ?int {
    try {
      $stmt = $this->connection->prepare('insert into a_author set name = ?');
      $this->connection->beginTransaction();
      $stmt->execute([$authorName]);
      $id = (int)$this->connection->lastInsertId();
      $this->connection->commit();
      return $id;
    } catch (PDOException $e) {
      $this->connection->rollBack();
      $result = $this->connection->prepare("select id from a_author where name = :authorName");
      $result->bindParam(':authorName', $authorName);
      $result->execute();
      return $result?->fetchColumn() ?? null;
    }
  }

  public function createDefinition(string $term, array $definition, int $authorId): ?int {
    global $slugify;
    try {
      $stmt = $this->connection->prepare('insert into a_definition set term = ?, example = ?, def = ?, author_id = ?, score_up = ?, score_down = ?, createdAt = ?, slug = ?');
      $this->connection->beginTransaction();
      $slug = $slugify->slugify($term);
      $date = DateTime::createFromFormat('d M Y', $definition['createdAt'])->format('Y-m-d');
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

  public function linkTagsToDefinition(int $definitionId, array $tagList): void {
    try {
      $stmt = $this->connection->prepare('insert into a_tag_definition set tag_id = ?, definition_id = ?');
      $this->connection->beginTransaction();
      foreach ($tagList as $tagId) {
        $stmt->execute([$tagId, $definitionId]);
      }
      $this->connection->commit();
    } catch (PDOException $e) {
      $this->connection->rollBack();
    }
  }

  public function addDefinition(string $termName, array $definition): void {
    global $slugify;
    if (!$definition['author']) {
      $definition['author'] = 'Orfan';
    }
    $authorSlug = $slugify->slugify($definition['author']);
    if (isset($this->authorMap[$authorSlug])) {
      $authorId = $this->authorMap[$authorSlug];
    } else {
      $authorId = $this->createAuthor($definition['author']);
      $this->authorMap[$authorSlug] = $authorId;
    }

    $id = $this->createDefinition($termName, $definition, $authorId);
    if ($id === null) {
      return;
    }

    $tagIdList = [];
    foreach ($definition['tags'] as $tag) {
      $slug = $slugify->slugify($tag);
      if (isset($this->tagMap[$slug])) {
        $tagIdList[] = $this->tagMap[$slug];
      } else {
        $tagId = $this->createTag($tag, $slug);
        $tagIdList[] = $tagId;
        $this->tagMap[$slug] = $tagId;
      }
    }

    $this->linkTagsToDefinition($id, $tagIdList);
  }

  public function createUndefinedTerm(string $term): ?int {
    try {
      $stmt = $this->connection->prepare('insert into a_undefined_term set term = ?');
      $this->connection->beginTransaction();
      $stmt->execute([$term]);
      $id = (int)$this->connection->lastInsertId();
      $this->connection->commit();
      return $id;
    } catch (PDOException $e) {
      $this->connection->rollBack();
      $result = $this->connection->prepare("select id from a_undefined_term where term = :termName");
      $result->bindParam(':termName', $term);
      $result->execute();
      return $result?->fetchColumn() ?? null;
    }
  }

  public function addUndefined(string $term, array $related): void {
    if (isset($this->undefinedMap[$term])) {
      $termId = $this->undefinedMap[$term];
    } else {
      $termId = $this->createUndefinedTerm($term);
      $this->undefinedMap[$term] = $termId;
    }

    try {
      $stmt = $this->connection->prepare('insert into a_related_definition set undefined_term_id = ?, term = ?, def = ?');
      $this->connection->beginTransaction();
      foreach ($related as $def) {
        $stmt->execute([$termId, $def['term'], $def['definition']]);
      }
      $this->connection->commit();
    } catch (PDOException $e) {
      echo 'Failed to add definition(s) for undefined term ' . $term . "\r\n";
      $this->connection->rollBack();
    }
  }

  public function fetchAuthors(): array {
    $stmt = $this->connection->query('select * from a_author');
    return $stmt->fetchAll();
  }
}
