<?php

namespace Drupal\dicto_views\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
      $cache = new CacheableMetadata();
      $cache->addCacheContexts(['url.query_args:q']);
      $cache->setCacheMaxAge(-1);
      $response = new CacheableJsonResponse($results);
      $response->addCacheableDependency($cache);

      return $response;
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
//    $query = $this->connection->query("
//        select any_value(nd.nid) as nid, any_value(nd.title) as name,
//               any_value(nfd.field_definitie_value) as description, max(vr.value) as value from node_field_data nd
//        inner join node__field_slug nfs on nd.nid = nfs.entity_id
//        inner join node__field_definitie nfd on nd.nid = nfd.entity_id
//        left join votingapi_result vr on nd.nid = vr.entity_id and vr.function = 'vote_count' and vr.type = 'score_up'
//        where nd.title like '" .$input . "%'
//        group by nfs.field_slug_value
//        order by value desc
//        limit 10");

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
    $response = new CacheableJsonResponse(mb_convert_encoding(['items' => $results], 'UTF-8', 'UTF-8'));
    $response->addCacheableDependency($cache);

    return $response;
  }

  public function randomPage(Request $request) {
    $query = $this->connection->query("
        select field_slug_value as slug
        from node__field_slug
                 inner join node_field_data on node_field_data.nid = node__field_slug.entity_id
        where bundle = 'definition'
        order by rand() desc
        limit 1;
        ");

    $slug = $query->fetchAssoc();
    $url = Url::fromRoute('view.term.term_page', ['arg_0' => $slug['slug']]);
    $uri = $url->toString(TRUE);

    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $response = new CacheableRedirectResponse($uri->getGeneratedUrl());
    $response->addCacheableDependency($cache);

    return $response;
  }

  public function accountRedirect(Request $request) {
    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      $url = Url::fromRoute('dicto_views.login');
      $uri = $url->toString(TRUE);

      $cache = new CacheableMetadata();
      $cache->setCacheMaxAge(0);
      $response = new CacheableRedirectResponse($uri->getGeneratedUrl());
      $response->addCacheableDependency($cache);

      return $response;
    }

    $url = Url::fromRoute('entity.user.edit_form', ['user' => $user->id()]);
    $uri = $url->toString(TRUE);

    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $response = new CacheableRedirectResponse($uri->getGeneratedUrl());
    $response->addCacheableDependency($cache);

    return $response;
  }

  public function addRedirect(Request $request) {
    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      $url = Url::fromRoute('dicto_views.login');
      $uri = $url->toString(TRUE);

      $cache = new CacheableMetadata();
      $cache->setCacheMaxAge(0);
      $response = new CacheableRedirectResponse($uri->getGeneratedUrl());
      $response->addCacheableDependency($cache);

      return $response;
    }

    $url = Url::fromRoute('node.add', ['node_type' => 'definition']);
    $uri = $url->toString(TRUE);

    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $response = new CacheableRedirectResponse($uri->getGeneratedUrl());
    $response->addCacheableDependency($cache);

    return $response;
  }

  public function authPage(Request $request) {
    return [
      '#theme' => 'dicto_views_auth_page',
      '#title' => 'Login',
    ];
  }
}
