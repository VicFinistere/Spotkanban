<?php

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$server = $url["host"];
$username = $url["user"];
$password = $url["pass"];
$db = substr($url["path"], 1);

// Our web handlers

$app->get('/', function() use($app) {
  $conn = new mysqli($server, $username, $password, $db);
  $sql = "SELECT * FROM task";
  $result = $conn -> query($sql);
  $tasks = fetch_all(MYSQLI_ASSOC);
  print_r($post);
  $app['monolog']->addDebug('logging output.');
  return $app['twig']->render('index.twig', ['tasks' => $tasks,]);
});

$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
