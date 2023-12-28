<?php

namespace Drupal\dicto_profit\Twig;

use Drupal\Core\Database\Connection;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

class TwigProfitCampaign extends AbstractExtension {
  private array $campaignPool = [];

  public function __construct(private Connection $connection) {
  }

  public function getFunctions():array {
    return [
      new TwigFunction('profit_campaign', [$this, 'profitCampaign']),
    ];
  }

  public function profitCampaign(string $bucket): Markup {
    if (empty($this->campaignPool)) {
      $this->loadCampaignPool();
    }

    foreach ($this->campaignPool as $key => $campaign) {
      if ($campaign['bucket'] === $bucket) {
        unset($this->campaignPool[$key]);

        return $this->renderCampaign($campaign);
      }
    }

    return new Markup('', 'UTF-8');
  }

  private function renderCampaign(array $campaign): Markup {
    $markup = "
    <div class='money-please'>
    <a title='{$campaign['name']}' href='{$campaign['referral_link']}' rel='nofollow'>
      <amp-img layout='responsive' width='{$campaign['width']}' height='{$campaign['height']}' src='{$campaign['src']}' alt='{$campaign['name']}'>
        <amp-img fallback layout='responsive' width='{$campaign['width']}' height='{$campaign['height']}' src='/bani-te-rog/{$campaign['id']}' alt='{$campaign['name']}'>
        </amp-img>' alt='{$campaign['name']}'>
        <noscript>
          <img width='{$campaign['width']}' height='{$campaign['height']}' src='{$campaign['src']}' alt='{$campaign['name']}'>
        </noscript>
      </amp-img>
    </a>
    </div>";

    return new Markup($markup, 'UTF-8');
  }

  private function loadCampaignPool() {
    $query = $this->connection->query('
    select
      pc.referral_link,
      pc.name,
      pci.src,
      pci.bucket,
      pci.width,
      pci.height,
      pci.id
    from profit_campaign pc
    inner join profit_campaign_image pci on pc.id = pci.campaign
    where pc.end_at > UNIX_TIMESTAMP()
    order by rand() * pc.weight desc
    limit 50');

    $this->campaignPool = $query->fetchAll(\PDO::FETCH_ASSOC);
  }
}
