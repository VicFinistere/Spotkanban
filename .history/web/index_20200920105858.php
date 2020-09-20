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
    $format = "SELECT * FROM task WHERE name LIKE %s";
    $req = sprintf($format, $task_name);
    
    $conn->set_charset("utf8");
    $fetched_task = mysqli_fetch_all($conn->query($req));
    return $fetched_task;
}

function remove_task($task_id){
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
  
  // DELETE 
  $format = "DELETE FROM task WHERE id IS '%s'";
  $req = sprintf($format, $task_id);

  if ($conn->query($req) === TRUE) {
    return "Record deleted successfully";
  } else {
    return "Error deleting record: " . $conn->error;
  }
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
  $format = "INSERT INTO task (name, status, description) VALUES ('%s', '%s', '%s') ";
  $req = sprintf($format, $task_name, $task_status, $task_description);
  $conn->set_charset("utf8");

  if ($conn->query($req) === TRUE) {
    echo "New record created successfully";
  } else {
    echo "Error: " . $req . "<br>" . $conn->error;
  }
  return $req;
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
    
    // GET TASK
    //$fetched_task = get_task($task_name);
    
    $msg = $task_id." : OK";
    // DELETE OLD TASK
    if($task_id != ''){
      $msg = remove_task($task_id);  
    }
  
    // CREATE TASK
    //$create_req = create_task($task_name, $task_description, $task_status);

    return json_encode(array('id' => $task_id));
  });


$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
