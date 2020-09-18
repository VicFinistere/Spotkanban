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
  // Use the database 
$conn = new mysqli($server, $username, $password, $db);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// On créé la requête
$req = "SELECT * FROM task";
 
// on envoie la requête
$res = $conn->query($req);
$tasks = list();

// on va scanner tous les tuples un par un
echo "<table>";
while ($data = mysqli_fetch_array($res)) {
    // on affiche les résultats
    $tasks.append($data['id']."</td><td>".$data['name']."</td></tr>");
}
echo "</table>";
  $app['monolog']->addDebug('logging output.');
  return $app['twig']->render('index.twig', ['tasks' => $tasks]);
});

$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
