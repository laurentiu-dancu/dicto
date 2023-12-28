<?php

namespace Drupal\dicto_profit\Service;

class Buckets {
  public const CONTENT = 'content';
  public const ASIDE = 'aside';

  public const HEADER = 'header';

  public function makeBuckets(array $banners): array {
    $buckets = [
      $this->findContent($banners),
      $this->findAside($banners),
      $this->findHeader($banners),
    ];

    return array_filter($buckets);
  }

  private function findContent(array $banners): array|null {
    $minWidth = 800;
    $minRatio = 0.3;
    $maxRatio = 0.999;

    foreach ($banners as $banner) {
      $foundBanner = $this->findValid($banner, $minWidth, $minRatio, $maxRatio);

      if ($foundBanner === null) {
        continue;
      }

      $foundBanner['bucket'] = self::CONTENT;

      return $foundBanner;
    }

    return null;
  }

  private function findAside(array $banners): array|null {
    $minWidth = 300;
    $minRatio = 1;
    $maxRatio = 3;

    foreach ($banners as $banner) {
      $foundBanner = $this->findValid($banner, $minWidth, $minRatio, $maxRatio);

      if ($foundBanner === null) {
        continue;
      }

      $foundBanner['bucket'] = self::ASIDE;

      return $foundBanner;
    }

    return null;
  }

  private function findHeader(array $banners): array|null {
    $minWidth = 800;
    $minRatio = 0.02;
    $maxRatio = 0.29999;

    foreach ($banners as $banner) {
      $foundBanner = $this->findValid($banner, $minWidth, $minRatio, $maxRatio);

      if ($foundBanner === null) {
        continue;
      }

      $foundBanner['bucket'] = self::HEADER;

      return $foundBanner;
    }

    return null;
  }

  private function findValid(array $banner, int $minWidth, $minRatio, $maxRatio): array|null {
    $width = $banner['width'] ?? 1;
    $height = $banner['height'] ?? 1;
    $ratio = $height / $width;

    if ($width < $minWidth) {
      return null;
    }

    if ($ratio < $minRatio) {
      return null;
    }

    if ($ratio > $maxRatio) {
      return null;
    }

    $banner['src'] = 'https:' . $banner['src'];

    return $banner;
  }
}
