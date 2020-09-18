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

// Use the database 

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// On créé la requête
$req = "SELECT * FROM task";
 
$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  $conn = new mysqli($server, $username, $password, $db);
  $tasks = $conn->query($req);
  return $app['twig']->render('index.twig', ['tasks' => $tasks]);
});

$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
