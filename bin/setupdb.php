<?php

$schemaPath = __DIR__ . '/../data/schema.sql';

$config = require(__DIR__ . '/../config.php');

$dsn = sprintf('sqlite:%s', $config['db.path']);

if(file_exists($config['db.path'])) {
    unlink($config['db.path']);
}


try {
    $pdo = new PDO($dsn, null, null, [PDO::ERRMODE_EXCEPTION => true]);
} catch(PDOException $error)
{
    echo 'Could not establish connection to db' . PHP_EOL;
    exit(1);
}

$pdo->exec(file_get_contents($schemaPath));

if(0 != $pdo->errorCode()) {
    echo sprintf('DB setup not successful (code: %d): ', $pdo->errorCode());
    echo implode(PHP_EOL, $pdo->errorInfo()) . PHP_EOL;
    exit(1);
}

$prepared = $pdo->prepare('INSERT INTO cards VALUES(null, :cardNumber, :expiryDate)');

for($i=0; $i<100; $i++) {


    $expiryDate = new DateTime('now', new DateTimeZone('Europe/London'));

    $randomTime = new DateInterval(sprintf('PT%dS', rand(60, 3600*7)));

    $expiryDate->add($randomTime);

    // Super nasty line for generating a fake card number, don't even look at it.
    $cardNumber = rtrim(chunk_split(substr(strtoupper(md5(microtime())),0, 12), 3,'.'),'.');

    $prepared->bindValue('cardNumber', $cardNumber);
    $prepared->bindValue('expiryDate', $expiryDate->format('c'));

    $prepared->execute();
}

echo 'ok' . PHP_EOL;
