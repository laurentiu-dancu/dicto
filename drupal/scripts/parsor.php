<?php

$autoloader = require_once './web/autoload.php';
require_once 'RepositoryB.php';

use Symfony\Component\DomCrawler\Crawler;

$repository = new RepositoryB();
$slugify = new \Cocur\Slugify\Slugify();

// Construct the iterator
$it = new RecursiveDirectoryIterator('/var/www/html/other/expresion');
$dirs = [];
// Loop through files
foreach(new RecursiveIteratorIterator($it) as $file) {
  if ($file->getExtension() == 'aspx') {
    $dirs[] = $file->getPathname();
  }
}

$i = 0;
foreach ($dirs as $file) {
  parseFile($file, $repository);
  $i++;
  if ($i % 100 === 0) {
    echo "$i \r\n";
    echo memory_get_usage() . " <- memory used\r\n";
    echo memory_get_peak_usage() . " <- memory peak\r\n";
  }
}

function parseFile(string $filename, RepositoryB $repository) {
  $doc = new \DOMDocument();
  @$doc->loadHTML(file_get_contents($filename));
  $crawler = new Crawler();
  $crawler->addDocument($doc);
  $term = $crawler->filter('div.post');
  $termName = trim($term->filter('h3')->first()->text(''));
  $bodies = processBodies($term->filter('div.post-body'));
  $footers = processFooters($term->filter('p.post-footer'));
  $definitions = [];
  foreach ($bodies as $key => $body) {
    if (!isset($footers[$key])) {
      continue;
    }
    $definitions[] = array_merge($body, $footers[$key]);
  }
  foreach ($definitions as $definition) {
    $repository->addDefinition($termName, $definition);
  }
}

function processBodies(Crawler $node): array {
  $results = $node->each(function (Crawler $definition, $i) {
    $values['definition'] = $definition->filter('td')->eq(0)->filter('b')->text('', FALSE);
    $values['example'] = $definition->filter('td')->eq(1)->filter('i')->text('', false);
    $tags = $definition->filter('td')->eq(2)->filter('a')->each(function (Crawler $tag, $i) {
      $tag = $tag->text('');
      $tag = str_replace('Ä', 'ă', $tag);
      $tag = str_replace('Ă˘', 'â', $tag);
      $tag = str_replace('Č', 'ț', $tag);
      $tag = str_replace('ĹŁ', 'ț', $tag);
      $tag = str_replace('Ĺ', 'ș', $tag);
      $tag = str_replace('Č', 'ș', $tag);
      $tag = str_replace('ĂŽ', 'î', $tag);
      return $tag;
    });

    $values['tags'] = [];
    foreach ($tags as $tag) {
      $tagArray = explode(';', $tag);
      foreach ($tagArray as $tagItem) {
        if ($tagItem !== '') {
          $tagArray2 = explode('.', $tagItem);
          foreach ($tagArray2 as $tagItemSecond) {
            if (strlen($tagItemSecond) > 1) {
              $tagArray3 = explode(' ', $tagItem);
              foreach ($tagArray3 as $tagItemTheThird) {
                if (strlen($tagItemTheThird) > 2) {
                  $values['tags'][] = trim($tagItemTheThird);
                }
              }
              $values['tags'][] = trim($tagItemSecond);
            }
          }
        }
      }
    }
    $values['tags'] = array_unique($values['tags']);

    if (str_contains($values['definition'], 'http') || str_contains($values['example'], 'http')) {
      $values['definition'] = '';
    }

    return $values;
  });

  $filterResults = [];
  foreach ($results as $result) {
    if ($result['definition'] !== '') {
      $filterResults[] = $result;
    }
  }

  return $filterResults;
}

function processFooters(Crawler $node): array {
  $results = $node->each(function (Crawler $definition, $i) {
    $values['author'] = $definition->filter('a')->first()->text('');
    $createdText = explode('@ ', $definition->filter('em')->first()->text(''));
    $values['createdAt'] = $createdText[1] ?? '';
    $scores = explode("   ", \trim($definition->filter('span')->first()->text(''), " "));
    $values['scoreUp'] = $scores[0] ?? '0';
    $values['scoreDown'] = $scores[1] ?? '0';

    return $values;
  });

  return $results;
}
