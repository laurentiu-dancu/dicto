<?php

namespace Drupal\dicto_profit;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;

class ProfitClient
{
  const CLIENT_HOST = 'api.profitshare.ro';

  public function __construct(private Client $client)
  {
  }

  public function campaigns(int $page = 1)
  {
    return $this->get("affiliate-campaigns?page=$page");
  }

  public function products()
  {
    return $this->get('affiliate-products');
  }

  public function links(array $links)
  {
    return $this->post('affiliate-links', $links);
  }

  private function get(string $route)
  {
    $method = 'GET';
    $headers = $this->profitHeaders($method, $route);
    $response = $this->client->get(self::CLIENT_HOST . '/' . $route, [
      'headers' => $headers,
    ]);

    $result = $response->getBody()->getContents();

    return \json_decode($result, true);
  }

  private function post(string $route, array $body)
  {
    $method = 'POST';
    $headers = $this->profitHeaders($method, $route);
    $response = $this->client->post(self::CLIENT_HOST . '/' . $route, [
      'headers' => $headers,
      'form_params' => $body,
    ]);

    $result = $response->getBody()->getContents();

    return \json_decode($result, true);
  }

  private function profitHeaders(string $method, string $route): array
  {
    $profit = Settings::get('profit');
    $user = $profit['user'] ?? 'no-user';
    $key = $profit['key'] ?? 'no-key';

    $date = gmdate('D, d M Y H:i:s T', time());
    $signature_string = "$method$route/". $user . $date;
    $auth = hash_hmac('sha1', $signature_string, $key);

    return [
      'Date' => $date,
      'X-PS-Client' => $user,
      'X-PS-Accept' => 'json',
      'X-PS-Auth' => $auth,
    ];
  }
}
