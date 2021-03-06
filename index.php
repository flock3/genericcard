<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
use Endroid\QrCode\QrCode;

require 'vendor/autoload.php';

$config = require(__DIR__ . '/config.php');

$dsn = sprintf('sqlite:%s', $config['db.path']);

$app = new \Slim\App;

try {
    $pdo = new PDO($dsn, null, null, [PDO::ERRMODE_EXCEPTION => true]);
} catch(PDOException $error)
{
    $app->errorHandler(null, null, $error);
    return;
}



$app->get('/hello/{cardNumber}', function (Request $request, Response $response) use($pdo) {
    $cardNumber = $request->getAttribute('cardNumber');

    $prepared = $pdo->prepare('SELECT expiryDate from cards WHERE cardData = :cardNumber');

    $prepared->bindParam('cardNumber', $cardNumber);

    $prepared->execute();

    $data = $prepared->fetch(PDO::FETCH_COLUMN);

    if(!$data) {
        return $response->withJson(false);
    }

    $expiryDate = DateTime::createFromFormat('c', $data);

    if($expiryDate > new DateTime('now', new DateTimeZone('Europe/London'))) {
        return $response->withJson(true);
    }

    return $response->withJson(false);
});

$app->get('/random', function (Request $request, Response $response) {
    return $response->getBody()->write(require(__DIR__ .'/data/random.php'));
});


$app->get('/random/qr.png', function (Request $request, Response $response) use($pdo) {

    $data = $pdo->query('SELECT * FROM cards ORDER BY RANDOM() LIMIT 1')->fetch(PDO::FETCH_ASSOC);

    $cardNumber = $data['cardData'];
    $expiryDate = $data['expiryDate'];

    $qrCode = new QrCode();
    $qrCode
        ->setText(sprintf('{%s}', $cardNumber))
        ->setSize(300)
        ->setPadding(10)
        ->setErrorCorrection('high')
        ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
        ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
        ->setLabel($expiryDate)
        ->setLabelFontSize(16)
        ->setImageType(QrCode::IMAGE_TYPE_PNG)
    ;

    $response = $response->withHeader('Content-Type', $qrCode->getContentType());

    $response->getBody()->write($qrCode->get());

    return $response;
});



$app->run();
