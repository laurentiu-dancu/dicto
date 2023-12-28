<?php

namespace Drupal\dicto_profit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dicto_profit\ProfitClient;
use Drupal\dicto_profit\Service\Campaigns;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfitController extends ControllerBase
{
  public function __construct(
    private ProfitClient $client,
    private Campaigns $campaigns,
  ){
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('dicto_profit.client'),
      $container->get('dicto_profit.campaigns'),
    );
  }

  public function demo()
  {
    $links = [];
    $links[] = [
      'name' => 'internal-id',
      'url' => 'https://carturesti.ro/offer/christmas-spice-everything-nice-5196',
    ];
    $campaigns = $this->client->campaigns(2);

    return new JsonResponse($campaigns);
  }

  public function cron()
  {
    $this->campaigns->import();

    return new JsonResponse(['result' => 'OK boomer']);
  }
}
