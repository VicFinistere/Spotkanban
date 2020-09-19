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

  $app->post('/handleTask', function(Request $request) use($app) {
    $task_id = $request->get('id');
    $task_name = $request->get('name');
    $task_description = $request->get('description');
    $task_status = $request->get('status');
    $app['monolog']->addDebug('ID : '$task_id);
    $app['monolog']->addDebug('Name : '$task_name);
    $app['monolog']->addDebug('Description : '$task_description);
    $app['monolog']->addDebug('Status : '$task_status);
    $response = new \Symfony\Component\HttpFoundation\JsonResponse();
    $response->setContent(json_encode(array('data' => $task_name), JSON_NUMERIC_CHECK));
    return $response;    
  });


$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
