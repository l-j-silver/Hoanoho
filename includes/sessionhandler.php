<?php
// Add CSP - see http://content-security-policy.com - Generator: http://cspisawesome.com
foreach (array("Content-Security-Policy", "X-Content-Security-Policy", "X-WebKit-CSP") as $headername) {
  header($headername.": default-src 'none'; script-src 'self' 'unsafe-inline'; object-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data http://www.wettergefahren.de; media-src 'self'; frame-src 'self'; font-src 'self'; connect-src 'self' wss: ws:");
}

session_set_cookie_params(
    0,
    "/",
    $_SERVER["HTTP_HOST"],
    true,
    true
);
session_name('HOANOHOSESSID');

if (!isset($_SESSION))
  session_start();

if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != "") {
  $querystring = "?".$_SERVER['QUERY_STRING'];
} else {
  $querystring = "";
}

require_once dirname(__FILE__).'/dbconnection.php';

// verify and update session
$loggedin = false;
if (
    isset($_SESSION['username']) &&
    $_SESSION['username'] != "" &&
    (
      !isset($_GET['login']) ||
      (isset($_SESSION['quicklogin_newsession']) && $_SESSION['quicklogin_newsession'] === true) )
  ) {
  $result = mysql_query("SELECT users.uid, password, users.hash, grpname, isAdmin, lastactivity from users left join usergroups on users.uid = usergroups.uid left join groups on groups.gid = usergroups.gid  where users.username = '" . mysql_real_escape_string($_SESSION['username']) . "' limit 1");
  while ($row = mysql_fetch_object($result)) {
    if (
          (isset($_SESSION['quicklogin']) && $_SESSION['quicklogin'] == $row->hash && strtotime($row->lastactivity) + (60 * 60 * 24 * 7 * 6) > time()) ||
          strtotime($row->lastactivity) + 900 > time()
      ) {
      $loggedin = true;
      if (isset($_SESSION['quicklogin_newsession']))
        unset($_SESSION['quicklogin_newsession']);
      $_SESSION['isAdmin'] = $row->isAdmin;
      $_SESSION['uid'] = $row->uid;
      $sql = "UPDATE users set lastactivity = now() where uid = " . $row->uid;
      mysql_query($sql);
    }
  }
}

// kill any existing session data and redirect to login page
// in case user could not be verified
if(!$loggedin) {
  if (isset($_SESSION)) {
    foreach($_SESSION as $key => $value) {
      unset($_SESSION[$key]);
    }
  }

  // keep information of origin
  $_SESSION['REAL_REFERER'] = $_SERVER['REQUEST_URI'];

  $uri = array_pop( explode("/", dirname($_SERVER['SCRIPT_NAME'])) );

  if ($uri == "pupnp") {
    header('Location: ../../../login.php'.$querystring);
  } elseif ($uri == "tablet" || $uri == "helper-client") {
      header('Location: ../login.php'.$querystring);
  } else {
    header('Location: ./login.php'.$querystring);
  }
  exit;
}

require_once dirname(__FILE__).'/getConfiguration.php';
