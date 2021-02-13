<?php

$autoloader = require_once './web/autoload.php';
require_once 'Repository.php';

use Symfony\Component\DomCrawler\Crawler;

$repository = new Repository();

// Construct the iterator
$it = new RecursiveDirectoryIterator('/var/www/html/sample');
$dirs = [];
// Loop through files
foreach(new RecursiveIteratorIterator($it) as $file) {
  if ($file->getExtension() == 'html') {
    $dirs[] = $file->getPathname();
  }
}

$dirs = array_reverse($dirs);

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

function parseFile(string $filename, $repository) {
  $doc = new \DOMDocument();
  @$doc->loadHTML(file_get_contents($filename));
  $crawler = new Crawler();
  $crawler->addDocument($doc);
  $term = $crawler->filter('#mainWordContainer');
  $termName = trim($term->filter('h1')->first()->text(''));
  if ($termName === '') {
    $termName = $crawler->filter('#MiddleCol > a')->first()->text('');
    $relatedTerms = processUndefined($crawler->filter('#MiddleCol li'));
    $repository->addUndefined($termName, $relatedTerms);
    return;
  }
  $definitions = processDefined($term->filter('li.dictando'));
  foreach ($definitions as $definition) {
    $repository->addDefinition($termName, $definition);
  }
}

function processUndefined(Crawler $node): array {
  return $node->each(function (Crawler $term) {
    $related['term'] = $term->filter('a')->first()->text('');
    $related['definition'] = '';
    $defList = $term->filterXPath('//li/text()')->extract(['_text']);
    foreach ($defList as $def) {
      if (trim($def) !== '') {
        $related['definition'] = trim($def);
      }
    }
    return $related;
  });
}

function processDefined(Crawler $node): array {
  $results = $node->each(function (Crawler $definition, $i) {
    $values['definition'] = trim($definition->filterXPath('//li/div[1]/text()')->extract(['_text'])[1] ?? '');
    $values['example'] = $definition->filter('.detailExample')->text('');
    $values['example'] = str_replace('Exemple: ', '', $values['example']);
    $tags = $definition->filter('.detailTagsContainer a')->each(function (Crawler $tag, $i) {
      return $tag->text('');
    });

    $values['tags'] = [];
    foreach ($tags as $tag) {
      $tagArray = explode(';', $tag);
      foreach ($tagArray as $tagItem) {
        if ($tagItem !== '') {
          $tagArray2 = explode('.', $tagItem);
          foreach ($tagArray2 as $tagItemSecond) {
            if ($tagItemSecond !== '') {
              $values['tags'][] = trim($tagItemSecond);
              $tagArray3 = explode(' ', $tagItem);
              foreach ($tagArray3 as $tagItemTheThird) {
                if (strlen($tagItemTheThird) > 2) {
                  $values['tags'][] = trim($tagItemTheThird);
                }
              }
            }
          }
        }
      }
    }
    $values['tags'] = array_unique($values['tags']);
    $values['author'] = $definition->filter('.detailAuthorContainer > a')->eq(0)->text('');
    $values['createdAt'] = $definition->filter('.detailAuthorContainer > a')->eq(1)->text('');
    $values['scoreUp'] = $definition->filter('.detailThumbs > span')->eq(0)->text('');
    $values['scoreDown'] = $definition->filter('.detailThumbs > span')->eq(1)->text('');

    return $values;
  });

  $last = end($results);
  if ($last['definition'] === '') {
    array_pop($results);
  }

  return $results;
}
