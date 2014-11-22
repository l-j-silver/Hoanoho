<?php
require_once dirname(__FILE__).'/dbconnection.php';
require_once dirname(__FILE__).'/getConfiguration.php';

if ($__CONFIG['php_debugbar'] == "1") {
	require_once dirname(__FILE__).'/../vendor/autoload.php';
	use DebugBar\StandardDebugBar;
	$debugbar = new StandardDebugBar();
	$debugbarRenderer = $debugbar->getJavascriptRenderer();
}

// Add CSP - see http://content-security-policy.com - Generator: http://cspisawesome.com
$imgsrc_exceptions = "http://www.wettergefahren.de http://*:32469";
$scriptsrc_exceptions = "";
$framesrc_exceptions = "";

// building webcam exceptions
$sql = "select dev_id from devices join device_types on devices.dtype_id = device_types.dtype_id join types on types.type_id = device_types.type_id where types.name = 'Webcam'";
$result = mysql_query($sql);
while ($device = mysql_fetch_object($result)) {
    // ip-address
    $sql = "select value from configuration where configuration.dev_id = ".$device->dev_id." and configstring = 'ipaddress'";
    $result2 = mysql_query($sql);
    $resultArr = mysql_fetch_assoc($result2);
    $cam_ipaddress = $resultArr['value'];
    // port
    $sql = "select value from configuration where configuration.dev_id = ".$device->dev_id." and configstring = 'port'";
    $result2 = mysql_query($sql);
    $resultArr = mysql_fetch_assoc($result2);
    $cam_port = $resultArr['value'];

    $imgsrc_exceptions .= " http://".$cam_ipaddress.":".$cam_port;
    $scriptsrc_exceptions .= " http://".$cam_ipaddress.":".$cam_port;
}  

// FHEM exceptions
if (substr($__CONFIG['fhem_url_admin'], 0, 4) == "http")
  $framesrc_exceptions .= " ".$__CONFIG['fhem_url_admin'];
if (substr($__CONFIG['fhem_url_web'], 0, 4) == "http")
  $framesrc_exceptions .= " ".$__CONFIG['fhem_url_web'];
if (substr($__CONFIG['fhem_url_mobile'], 0, 4) == "http")
  $framesrc_exceptions .= " ".$__CONFIG['fhem_url_mobile'];
if (substr($__CONFIG['fhem_url_tablet'], 0, 4) == "http")
  $framesrc_exceptions .= " ".$__CONFIG['fhem_url_tablet'];

foreach (array("Content-Security-Policy", "X-Content-Security-Policy", "X-WebKit-CSP") as $headername) {
  header($headername.": default-src 'none'; script-src 'self' 'unsafe-inline' 'unsafe-eval' data: ".$scriptsrc_exceptions."; object-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: ".$imgsrc_exceptions."; media-src 'self'; frame-src 'self'".$framesrc_exceptions."; font-src 'self'; connect-src 'self' wss: ws:");
}

// session cookie settings
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

// verify and update session
$loggedin = false;
if (
    isset($_SESSION['username']) &&
    $_SESSION['username'] != "" &&
    (
      !isset($_GET['login']) ||
      (isset($_SESSION['quicklogin_newsession']) && $_SESSION['quicklogin_newsession'] === true) )
  ) {
  $result = mysql_query("SELECT users.uid, password, users.hash, grpname, isAdmin from users left join usergroups on users.uid = usergroups.uid left join groups on groups.gid = usergroups.gid  where users.username = '" . mysql_real_escape_string($_SESSION['username']) . "' limit 1");
  while ($row = mysql_fetch_object($result)) {
    if (
          (isset($_SESSION['quicklogin']) && $_SESSION['quicklogin'] == $row->hash && $_SESSION['lastactivity'] + (60 * 60 * 24 * 7 * 6) > time()) ||
          $_SESSION['lastactivity'] + 900 > time()
      ) {
      $loggedin = true;
      if (isset($_SESSION['quicklogin_newsession']))
        unset($_SESSION['quicklogin_newsession']);
      $_SESSION['isAdmin'] = $row->isAdmin;
      $_SESSION['uid'] = $row->uid;
      $_SESSION['lastactivity'] = time();
	  if ($row->isAdmin == 1)
		  header('X-FHEM-AllowAdmin: ' . session_id());
	  header('X-FHEM-AllowUser: ' . session_id());
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
