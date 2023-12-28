<?php

namespace Drupal\dicto_profit\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MoneyController extends ControllerBase{

  public function __construct(
    private Connection $connection,
  ){
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database'),
    );
  }

  public function moneyPlease(string $image): CacheableResponse {
    $image = (int) Xss::filter($image);
    $query = $this->connection->query("select src from profit_campaign_image where id = $image");
    $result = $query->fetchAll(\PDO::FETCH_ASSOC)[0]['src'] ?? 'https://uxwing.com/wp-content/themes/uxwing/download/emoji-emoticon/smile-icon.png';

    $cache = new CacheableMetadata();
    $cache
      ->setCacheMaxAge(24 * 3600 * 7)
      ->setCacheTags(["campaignImageProxy:$image"]);

    $file = file_get_contents($result);

    $response = new CacheableResponse($file, 200, [
      'content-type' => 'image/jpeg',
      'content-length' => strlen($file),
    ]);
    $response->addCacheableDependency($cache);

    return $response;
  }
}
