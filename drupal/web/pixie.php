<?php


ob_start();
header("Content-type: image/png");
header("Cache-control: no-store");
$data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
echo base64_decode($data);

header("Connection: close");
ob_end_flush();
ob_flush();
flush();

$clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
$clientAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$referrer = $_SERVER['HTTP_REFERER'] ?? null;

$parts = explode($host, $referrer);
$page = $parts[1] ?? null;

require '../vendor/autoload.php';

$db = new \IP2Location\Database('../vendor/ip2location/ip2location-php/data/IP2LOCATION-LITE-DB1.BIN', \IP2Location\Database::FILE_IO);
$records = $db->lookup($clientIp, \IP2Location\Database::ALL);
$countryCode = $records['countryCode'] ?? null;
$ipHash = md5($clientIp);

include 'sites/default/settings.php';
$database = $databases['default']['default'] ?? [];

$dsn = $database['driver'] . ':host=' . $database['host'] . ';dbname=' . $database['database'];
$dsn .= ';port=' . $database['port'] . ';collation=' . $database['collation'];

$options = [
  \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
  \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
  \PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new \PDO($dsn, $database['username'], $database['password'], $options);

$stmt = $pdo->prepare('insert into page_access_log set page = ?, ipHash = ?, country = ?, agent = ?, created = ?');
$pdo->beginTransaction();
$stmt->execute([$page, $ipHash, $countryCode, $clientAgent, time()]);
$pdo->commit();
