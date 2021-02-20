<?php

namespace Drupal\dicto_views\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Element\EntityAutocomplete;

class DictoViewsSearchController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }

    $input = Xss::filter($input);
    $query = $this->connection->query("
        select any_value(nd.nid) as nid, any_value(nd.title) as name,
               any_value(nfd.field_definitie_value) as description from node_field_data nd
        inner join node__field_slug nfs on nd.nid = nfs.entity_id
        inner join node__field_definitie nfd on nd.nid = nfd.entity_id
        where nd.title like '" .$input . "%'
        group by nfs.field_slug_value
        order by nid desc
        limit 10");

    $nodes = $query->fetchAll();

    foreach ($nodes as $node) {
      $termLen = strlen($node->name);

      $description = $node->description;
      if (strlen($description) + $termLen > 100) {
        $description = substr($description, 0, 98 - $termLen) . '...';
      }

      $results[] = [
        'value' => $node->name,
        'label' => '<strong>' . $node->name . '</strong> - <em>' . $description . '</em>',
      ];
    }

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url.query_args:q']);
    if (count($results) !== 10) {
      $cache->addCacheTags(['node_list']);
    }
    $cache->setCacheMaxAge(3600);
    $response = new CacheableJsonResponse(mb_convert_encoding($results, 'UTF-8', 'UTF-8'));
    $response->addCacheableDependency($cache);

    return $response;
  }
}
