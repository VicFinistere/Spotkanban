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


function get_task($task_name){
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
    
    // SELECT 
    $req = "SELECT * FROM task WHERE name LIKE "."'".$task_name."'";
    
    $conn->set_charset("utf8");
    $fetched_task = mysqli_fetch_all($conn->query($req));
    return $fetched_task;
}

function remove_task($task_name){
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
  
  // SELECT 
  $req = "DELETE FROM task WHERE name LIKE ";
  $req .= "'";
  $req .= $task_name;
  $req .="'";

  if ($conn->query($req) === TRUE) {
    echo "Record deleted successfully";
  } else {
    echo "Error deleting record: " . $conn->error;
  }
  return $req;
}

function create_task($task_name, $task_description, $task_status){
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
  
  // SELECT 
  $req = "INSERT INTO task (name, status, description) VALUES ("."'".$task_name."'".", "."'".$task_status."'".", "."'".$task_description."'".")";
  
  $conn->set_charset("utf8");
  if ($conn->query($req) === TRUE) {
    echo "New record created successfully";
  } else {
    echo "Error: " . $req . "<br>" . $conn->error;
  }
}

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
    $fetched_task = get_task($task_name);
    $remove_req = remove_task($task_name);
    create_task($task_name, $task_description, $task_status);
    $response = new \Symfony\Component\HttpFoundation\JsonResponse();
    //$response->setContent(json_encode(array('data' => $fetched_task), JSON_NUMERIC_CHECK));
    $response->setContent(json_encode(array('data' => $remove_req), JSON_NUMERIC_CHECK));

    return $response;    
  });


$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
