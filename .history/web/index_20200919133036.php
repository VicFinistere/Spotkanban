<?php

require('../vendor/autoload.php');
use Symfony\Component\HttpFoundation\Request;

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



function get_tasks()
{
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);

  // Use the database 
  $conn = new mysqli($server, $username, $password, $db);

  // Check connection
  if ($conn->connect_error) 
  {
    die("Connection failed: " . $conn->connect_error);
  }
  $conn->set_charset("utf8");
  return mysqli_fetch_all($conn->query("SELECT * FROM task"));
}

// Our web handlers

$app->get('/', function() use($app) {
    $tasks = get_tasks();
    $app['monolog']->addDebug('logging index output.');
    return $app['twig']->render('index.twig', ['tasks' => $tasks]);
  });

  $app->post('/add_task', function(Request $request) use($app) {
    $task_id = $request->query->get('idSelect');
    $task_name = $request->query->get('name');
    $task_description = $request->query->get('description');
    $task_status = $request->query->get('status');
    $app['monolog']->addDebug('logging output.');
    return $app->json([$task_id, $task_name, $task_description, $task_status], Response::HTTP_OK)->setEncodingOptions(JSON_NUMERIC_CHECK);
  });

  $app->post('/update_task', function(Request $request) use($app) {
    $idSelect = $request->request->get('idSelect');
    $app['monolog']->addDebug('logging output.');
    return $app->json("UPDATE", Response::HTTP_OK)->setEncodingOptions(JSON_NUMERIC_CHECK);
  });


$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
