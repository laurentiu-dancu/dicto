<?php

namespace Drupal\dicto_profit\Service;

use Drupal\Core\Database\Connection;
use Drupal\dicto_profit\ProfitClient;

class Campaigns {
  public function __construct(
    private ProfitClient $client,
    private Buckets $buckets,
    private Connection $connection,
  ) {
  }

  public function import() {
    $campaigns = $this->queryCampaigns();
    $campaigns = $this->filterNotCreated($campaigns);
    $campaigns = $this->mapImageBuckets($campaigns);
    $campaigns = $this->filterNoBuckets($campaigns);
    $campaigns = $this->mapReferralLinks($campaigns);

    $this->persistCampaigns($campaigns);
    $this->cleanupExpired();
    $this->updateWeights();
  }

  private function queryCampaigns(): array {
    $response = $this->client->campaigns();
    $campaignPages = [];
    $campaignPages[] = $response['result']['campaigns'] ?? [];

    $paginator = $response['result']['paginator'] ?? [];
    $totalPages = $paginator['totalPages'] ?? 1;

    for ($i = 2; $i <= $totalPages; $i++) {
      $response = $this->client->campaigns($i);
      $campaignPages[] = $response['result']['campaigns'] ?? [];
    }

    return \array_merge(...$campaignPages);
  }

  private function filterNotCreated(array $campaigns): array {
    $query = $this->connection->query('select id, profit_id from profit_campaign');
    $existingIds = $query->fetchAll(\PDO::FETCH_ASSOC);
    $existingIdsMap = array_column($existingIds, 'id', 'profit_id');

    $filteredCampaigns = [];

    foreach ($campaigns as $campaign) {
      if (!isset($existingIdsMap[$campaign['id']])) {
        $filteredCampaigns[] = $campaign;
      }
    }

    return $filteredCampaigns;
  }

  private function mapImageBuckets(array $campaigns): array {
    foreach ($campaigns as $key => $campaign) {
      $campaigns[$key]['buckets'] = $this->buckets->makeBuckets($campaign['banners'] ?? []);
    }

    return $campaigns;
  }

  private function filterNoBuckets(array $campaigns): array {
    $filteredCampaigns = [];

    foreach ($campaigns as $key => $campaign) {
      if (count($campaign['buckets']) > 0) {
        $filteredCampaigns[] = $campaign;
      }
    }

    return $filteredCampaigns;
  }

  private function mapReferralLinks(array $campaigns): array {
    $links = [];

    foreach ($campaigns as $campaign) {
      $link['name'] = 'api-campaign-' . $campaign['id'] . '_' . $campaign['name'];
      $link['url'] = $campaign['url'];
      $links[] = $link;
    }

    $links = $this->client->links($links);
    $links = $links['result'] ?? [];
    $mappedLinks = array_column($links, 'ps_url', 'url');

    foreach ($campaigns as $key => $campaign) {
      $campaigns[$key]['referral_link'] = $mappedLinks[$campaign['url']] ?? null;
    }

    return $campaigns;
  }

  private function cleanupExpired(): void {
    $query = $this->connection
      ->query('delete from profit_campaign where end_at < UNIX_TIMESTAMP()');
    $query->execute();

        $query = $this->connection
      ->query('
           delete pci from profit_campaign_image pci
           left join profit_campaign pc on pci.campaign = pc.id
           where pc.id is null');
    $query->execute();
  }

  private function updateWeights(): void {
    /**
     * select
     * pc.advertiser_id as advertiser_id,
     * count(advertiser_id) as campaign_count,
     * avg(round((pc.end_at - pc.start_at) / 3600 / 24)) as average_days,
     * log(avg(round((pc.end_at - pc.start_at) / 3600 / 24))) as average_days_log,
     * 1 / log(avg(round((pc.end_at - pc.start_at) / 3600 / 24))) as average_days_inverse_log,
     * (1 / log(avg(round((pc.end_at - pc.start_at) / 3600 / 24)))) / count(advertiser_id) as raw_weight,
     * round(100 * (1 / log(avg(round((pc.end_at - pc.start_at) / 3600 / 24)))) / count(advertiser_id)) as weight,
     * round(100 * (1 / log(avg(round((pc.end_at - pc.start_at) / 3600 / 24))))) as effective_weight
     * from profit_campaign pc group by advertiser_id order by effective_weight asc;
     */

    $query = $this->connection->query('
      UPDATE profit_campaign pc
          JOIN (
              select
                  pc.advertiser_id as advertiser_id,
                  count(advertiser_id) as campaign_count,
                  round(
                          10000
                              * (1 / (log(avg(round((pc.end_at - pc.start_at))) / (3600 * 24)) + 15))
                              * (1 / (log(count(advertiser_id) + 15)))
                  ) as weight
              from profit_campaign pc group by advertiser_id
          ) subquery ON pc.advertiser_id = subquery.advertiser_id
      SET pc.weight = subquery.weight;
    ');
    $query->execute();
  }

  private function persistCampaigns(array $campaigns): void {
    $em = \Drupal::entityTypeManager();
    $campaignStorage = $em->getStorage('dicto_profit_campaign');
    $imageStorage = $em->getStorage('dicto_profit_campaign_image');

    foreach ($campaigns as $campaign) {
      if (empty($campaign['referral_link'])) {
        continue;
      }

      $campaignEntity = $campaignStorage->create([
        'profit_id' => $campaign['id'],
        'name' => $campaign['name'] ?? '',
        'advertiser_id' => $campaign['advertiser_id'],
        'original_url' => $campaign['url'],
        'referral_link' => $campaign['referral_link'],
        'weight' => 1,
        'start_at' => $this->timeGuardian($campaign['startDate']),
        'end_at' => $this->timeGuardian($campaign['endDate']),
      ]);

      $campaignEntity->save();

      foreach ($campaign['buckets'] as $bucket) {
        $imageEntity = $imageStorage->create([
          'campaign' => $campaignEntity,
          'src' => $bucket['src'],
          'bucket' => $bucket['bucket'],
          'width' => $bucket['width'],
          'height' => $bucket['height'],
        ]);

        $imageEntity->save();
      }
    }
  }

  private function timeGuardian(string $datetimeString): int {
    $unixTime = \strtotime($datetimeString);

    if ($unixTime <= 0) {
      return 0;
    }

    if ($unixTime > 2147483000) {
      return 2147483000;
    }

    return $unixTime;
  }
}
