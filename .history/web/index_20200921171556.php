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

function get_member($member_name, $member_password){
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
  $format = "SELECT * FROM member WHERE username = '%s' AND password = '%s' ";
  $req = sprintf($format, $member_name, $member_password);
  
  $conn->set_charset("utf8");
  $fetched_task = mysqli_fetch_all($conn->query($req));
  $result = "Member : ".$fetched_task." ( ".$req." )";
  return $result;
}

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
  $format = "DELETE FROM task WHERE id = %s";
  $req = sprintf($format, $task_id);

  if ($conn->query($req) === TRUE) {
    return "Record deleted successfully";
  } else {
    return "Error deleting record: (".$req.")". $conn->error;
  }
}

function create_task($task_name, $task_description, $task_status, $task_team){
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
  if($task_status != ""){
    $format = "INSERT INTO task (name, status, description, team) VALUES ('%s', '%s', '%s', '%s') ";
    $req = sprintf($format, addslashes($task_name), addslashes($task_status), addslashes($task_description), addslashes($task_team));
  } else {
    $format = "INSERT INTO task (name, description, team) VALUES ('%s', '%s', '%s') ";
    $req = sprintf($format, addslashes($task_name), addslashes($task_description), addslashes($task_team));
  }
  $conn->set_charset("utf8");

  if ($conn->query($req) === TRUE) {
    return "New record created ( ".$req." )successfully";
  } else {
    return "Error: " . $req . "<br>" . $conn->error;
  }
}

function get_tasks($team="")
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
  
  $format = "SELECT * FROM task WHERE team LIKE '%s'";
  $req = sprintf($format, $team);
  return mysqli_fetch_all($conn->query($req));
}

// Our web handlers

$app->get('/', function() use($app) {
    $tasks = get_tasks();
    $app['monolog']->addDebug('logging index output.');
    return $app['twig']->render('index.twig', ['tasks' => $tasks]);
  });

  $app->get('/{team}', function($team) use($app) {
    $tasks = get_tasks($team);
    $app['monolog']->addDebug('logging index output.');
    return $app['twig']->render('index.twig', ['tasks' => $tasks]);
  });
  

  $app->post('/login', function(Request $request) use($app) {
    $member_name = $request->get('member_name');
    $member_password = $request->get('member_password');
    $member = get_member($member_name, $member_password);
    return json_encode(array('member_name' => $member_name, 'member_password' => $member_password, 'member' => $member));
  });

  $app->post('/handleTask', function(Request $request) use($app) {
    $task_id = $request->get('id');
    $task_name = $request->get('name');
    $task_description = $request->get('description');
    $task_status = $request->get('status');
    $task_team = $request->get('team');
    // GET TASK
    //$fetched_task = get_task($task_name);
    
    //$msg = $task_id." : OK";

    // DELETE OLD TASK
    if($task_id != ''){
      remove_task($task_id);  
    }
  
    // CREATE TASK
    $msg = create_task($task_name, $task_description, $task_status, $task_team);

    return json_encode(array('id' => $task_id, 'msg' => $msg, 'team' => $task_team));
  });


$app->get('/cowsay', function() use($app) {
  $app['monolog']->addDebug('cowsay');
  return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});


$app->post('/removeTask', function(Request $request) use($app) {
  
  $task_id = $request->get('id');
  $task_name = $request->get('name');
  $task_description = $request->get('description');
  $task_status = $request->get('status');
  
  // DELETE OLD TASK
  if($task_id != ''){
    $msg = remove_task($task_id);  
  }

  return json_encode(array('id' => $task_id, 'msg' => $msg));
});


$app->get('/cowsay', function() use($app) {
$app['monolog']->addDebug('cowsay');
return "<pre>".\Cowsayphp\Cow::say("Cool beans")."</pre>";
});

$app->run();
