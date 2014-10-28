<?php
require_once dirname(__FILE__).'/../includes/dbconnection.php';
require_once dirname(__FILE__).'/../includes/getConfiguration.php';
require_once dirname(__FILE__).'/../includes/password.php';

// Add strict CSP - see http://content-security-policy.com - Generator: http://cspisawesome.com
foreach (array("Content-Security-Policy", "X-Content-Security-Policy", "X-WebKit-CSP") as $headername) {
  header($headername.": default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self'; font-src 'self'");
}

$referer = "";

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

if (isset($_GET['cmd']) && $_GET['cmd'] == "logout" && isset($_SERVER['HTTP_REFERER'])) {
  $_SESSION['REAL_REFERER'] = $_SERVER['HTTP_REFERER'];
  $path = array_pop( explode("/", dirname( parse_url($_SERVER['HTTP_REFERER'])['path'] )) );
  $uri = array_pop( explode("/", parse_url($_SERVER['HTTP_REFERER'])['path'] ) );
}

if (
    isset($_SESSION['REAL_REFERER']) &&
    $_SESSION['REAL_REFERER'] != "" &&
    $_SESSION['REAL_REFERER'] != "/" &&
    $_SESSION['REAL_REFERER'] != "/index.php" &&
    (
      !isset($path) ||
        (
         ($path == "mobile" || $path == "tablet") ||
         ($path == "" && $uri != "index.php" && $uri != "")
        )
    )
  )
  $referer = $_SESSION['REAL_REFERER'];
elseif (isset($_POST['referer']) && $_POST['referer'] != "")
  $referer = $_POST['referer'];
else
  $referer = "./";

// Send customized headers to disallow any access to FHEM backends
// can be used in HAproxy setups to remove access to FHEM based on Hoanoho session
header('X-FHEM-DisallowAdmin: '.session_name().'='. session_id());
header('X-FHEM-DisallowUser: '.session_name().'='. session_id());

session_destroy();

// normal login
if (isset($_POST['cmd']) && isset($_POST['login_username']) && isset($_POST['login_password'])) {
    if (strlen($_POST['login_username']) > 0 && strlen($_POST['login_password']) > 0) {
        $result = mysql_query("SELECT users.uid, password, users.hash, grpname, isAdmin from users left join usergroups on users.uid = usergroups.uid left join groups on groups.gid = usergroups.gid  where username = '" . mysql_real_escape_string($_POST['login_username']) . "' limit 1");
        while ($row = mysql_fetch_object($result)) {
            if (password_verify($_POST['login_password'], $row->password)) {

                session_start();
                session_regenerate_id(true);

                $_SESSION['username'] = $_POST['login_username'];
                $_SESSION['isAdmin'] = $row->isAdmin;
                $_SESSION['uid'] = $row->uid;
                $_SESSION['logintime'] = time();
                $_SESSION['lastactivity'] = time();
                $_SESSION['mobile'] = true;

                // Update password hash if required
                if (password_needs_rehash($row->password, constant($__CONFIG['hash_algorithm']), json_decode($__CONFIG['hash_options'], true))) {
                    $password = password_hash(mysql_real_escape_string($_POST['login_password']), constant($__CONFIG['hash_algorithm']), json_decode($__CONFIG['hash_options'], true));
                    $hash = md5(mysql_real_escape_string($_POST['login_username']) + $password + time());

                    $sql = "update users set password = '" . $password . "', hash = '".$hash."' where uid = ". $row->uid;
                    if ($password != "" && $hash != "")
                      mysql_query($sql);
                }

                $sql = "UPDATE users set lastlogin = now() where uid = " . $row->uid;
                mysql_query($sql);
                $sql = "UPDATE users set lastactivity = now() where uid = " . $row->uid;
                mysql_query($sql);

                header('Location: ' . $referer );
                exit;
            }
        }
    }
}

// quick login
elseif (isset($_GET['login']) && $_GET['login'] != "") {
    $result = mysql_query("SELECT users.uid, password, users.hash, username, grpname, isAdmin from users left join usergroups on users.uid = usergroups.uid left join groups on groups.gid = usergroups.gid  where users.hash = '" . mysql_real_escape_string($_GET['login']) . "' limit 1");
    while ($row = mysql_fetch_object($result)) {

        session_start();
        session_regenerate_id(true);

        $_SESSION['username'] = $row->username;
        $_SESSION['isAdmin'] = $row->isAdmin;
        $_SESSION['uid'] = $row->uid;
        $_SESSION['logintime'] = time();
        $_SESSION['lastactivity'] = time();
        $_SESSION['mobile'] = true;
        $_SESSION['quicklogin'] = $_GET['login'];
        $_SESSION['quicklogin_newsession'] = true;

        $sql = "UPDATE users set lastlogin = now() where uid = " . $row->uid;
        mysql_query($sql);
        $sql = "UPDATE users set lastactivity = now() where uid = " . $row->uid;
        mysql_query($sql);

        if ($referer == "./")
          header('Location: ' . "./?login=" . $_GET['login'] );
        else
          header('Location: ' . $referer );
        exit;
    }
}
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <link rel="stylesheet" href="css/ratchet.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <script src="js/ratchet.js"></script>
        <script src="js/standalone.js"></script>

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - Anmelden</title>
    </head>
    <body>
        <header class="bar-title">
            <h1 class="title">Anmelden</h1>
        </header>

        <div class="content">
            <div class="content-padded">
              <p class="welcome">&nbsp;</p>
            </div>
            <form class="loginform" action="login.php" method="post" name="loginform">
                <ul class="list inset">
                    <li class="login">
                        <input type="text" name="login_username" placeholder="Benutzername" autofocus>
                    </li>
                    <li class="login">
                        <input type="password" name="login_password" placeholder="Passwort">
                    </li>
                </ul>

                <input type="hidden" name="cmd" value="login">
                <input type="hidden" name="referer" value="<?php echo $referer; ?>">

                <div class="content-padded">
                  <a class="button-block" href="#" onclick="javascript:document.loginform.submit();">Anmelden</a>
                </div>
            </form>

          <?php if (isset($__CONFIG['maintenance_msg']) && $__CONFIG['maintenance_msg'] != "") { ?>
          <ul class="list inset">
            <li class="list-divider">Systemnachricht</li>
            <li><?php echo $__CONFIG['maintenance_msg'] ?></li>
          </ul>
          <?php } ?>

          </div>
    </body>
</html>
