<?php
include dirname(__FILE__).'/../includes/password_compat/lib/password.php';
$passwd_algorithm = "PASSWORD_DEFAULT";
$passwd_options = array("cost" => 10);

// Add strict CSP - see http://content-security-policy.com - Generator: http://cspisawesome.com
header("Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self'; font-src 'self'");
header("X-Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self'; font-src 'self'");
header("X-WebKit-CSP: default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self'; font-src 'self'");

    $referer = "";

    if (!isset($_SESSION)) {
      session_start();
    }

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        if (isset($_GET['login']) && $_GET['login'] != "") {
          setcookie(
            session_name(),
            '',
            time() + (10 * 365 * 24 * 60 * 60),
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
          );
        } else {
          setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
          );
        }
    }

    if(isset($_GET['cmd']) && $_GET['cmd'] == "logout" && isset($_SERVER['HTTP_REFERER']))
        $_SESSION['REAL_REFERER'] = $_SERVER['HTTP_REFERER'];

    if(isset($_SESSION['REAL_REFERER'])) {
      $referer = $_SESSION['REAL_REFERER'];
    } elseif(isset($_POST['referer'])) {
      $referer = $_POST['referer'];
    }

    session_destroy();

    include dirname(__FILE__).'/../includes/dbconnection.php';

    $sql = "select configstring, value from configuration where dev_id = 0 order by configstring asc";
    $result = mysql_query($sql);

    $__CONFIG = array();

    while ($row = mysql_fetch_array($result)) {
        $__CONFIG[$row[0]] = $row[1];
    }

    // quick login
    if (isset($_GET['login']) && $_GET['login'] != "") {
        $result = mysql_query("SELECT users.uid, password, username, grpname, isAdmin from users left join usergroups on users.uid = usergroups.uid left join groups on groups.gid = usergroups.gid  where users.hash = '" . $_GET['login'] . "' limit 1");
        while ($row = mysql_fetch_object($result)) {

            session_start();

            $_SESSION['username'] = $row->username;
            $_SESSION['md5password'] = $row->password;
            $_SESSION['isAdmin'] = $row->isAdmin;
            $_SESSION['login'] = 1;
            $_SESSION['uid'] = $row->uid;
            $_SESSION['logintime'] = time();

            $sql = "UPDATE users set lastlogin = now() where uid = " . $row->uid;
            mysql_query($sql);

            if (isset($_POST['referer']))
              $uri = array_pop( explode("/", dirname($_POST['referer'])) );

            if (
                isset($_POST['referer']) &&
                $_POST['referer'] != "" &&
                $_POST['referer'] != "/" &&
                ($uri == "mobile" || $uri == "tablet" || $uri == "pupnp")
            ) {
                header('Location: '.$_POST['referer']);
            } else {
              header('Location: ./?login='.$_GET['login']);
            }
            exit;
        }
    }

    // normal login
    elseif (isset($_POST['cmd']) && isset($_POST['login_username']) && isset($_POST['login_password'])) {
        if (strlen($_POST['login_username']) > 0 && strlen($_POST['login_password']) > 0) {
            $result = mysql_query("SELECT users.uid,password, grpname, isAdmin from users left join usergroups on users.uid = usergroups.uid left join groups on groups.gid = usergroups.gid  where username = '" . $_POST['login_username'] . "' limit 1");
            while ($row = mysql_fetch_object($result)) {
                if (password_verify($_POST['login_password'], $row->password)) {
                    session_start();

                    $_SESSION['username'] = $_POST['login_username'];
                    $_SESSION['md5password'] = md5($_POST['login_password']);
                    $_SESSION['isAdmin'] = $row->isAdmin;
                    $_SESSION['login'] = 1;
                    $_SESSION['uid'] = $row->uid;
                    $_SESSION['logintime'] = time();

                    // Update password hash if required
                    if (password_needs_rehash($row->password, $passwd_algorithm, $passwd_options)) {
                        $password = password_hash($_POST['login_password'], $passwd_algorithm, $passwd_options);
                        $hash = md5($_POST['login_username'] + $password + time());

                        $sql = "update users set password = '" . $password . "', hash = '".$hash."' where uid = ".$_SESSION['uid'];
                        mysql_query($sql);
                    }

                    $sql = "UPDATE users set lastlogin = now() where uid = " . $row->uid;
                    mysql_query($sql);

                    if (isset($_POST['referer']))
                      $uri = array_pop( explode("/", dirname($_POST['referer'])) );

                    if (
                        isset($_POST['referer']) &&
                        $_POST['referer'] != "" &&
                        $_POST['referer'] != "/" &&
                        ($uri == "mobile" || $uri == "tablet" || $uri == "pupnp")
                    ) {
                        header('Location: '.$_POST['referer']);
                    } elseif(isset($_GET['login']) && $_GET['login'] != "") {
                        header('Location: ./?login='.$_GET['login']);
                    } else {
                        header('Location: ./');
                    }
                    exit;
                }
            }
        }
    }
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <?php include dirname(__FILE__).'/includes/mobile-app.php'; ?>

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
