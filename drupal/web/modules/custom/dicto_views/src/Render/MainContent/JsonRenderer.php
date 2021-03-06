<?php

namespace Drupal\dicto_views\Render\MainContent;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default main content renderer for JSON requests.
 */
class JsonRenderer implements MainContentRendererInterface {
  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    // At this point this is basically a hook.
    $json = [];
    $json['new'] = $main_content['#build_id'];
    $json['up'] = $main_content['results']['score_up']['vote_count'] ?? 0;
    $json['down'] = $main_content['results']['score_down']['vote_count'] ?? 0;
    $json['message'] = $main_content['results']['message'] ?? '';

    $response = new JsonResponse($json, 200);
    return $response;
  }

}
