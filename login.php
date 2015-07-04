<?php
require_once dirname(__FILE__).'/includes/dbconnection.php';
require_once dirname(__FILE__).'/includes/getConfiguration.php';
require_once dirname(__FILE__).'/includes/password.php';
require_once dirname(__FILE__).'/vendor/autoload.php';
use DebugBar\StandardDebugBar;

if ($__CONFIG['php_debugbar'] == "1") {
	$debugbar = new StandardDebugBar();
	$debugbarRenderer = $debugbar->getJavascriptRenderer();
}

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

// redirect to mobile login page for mobile devices
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$_SERVER['HTTP_USER_AGENT'])||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4))) {
  if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != "") {
    header('Location: ./mobile/login.php?'.$_SERVER['QUERY_STRING']);
  } else {
    header('Location: ./mobile/login.php');
  }
  exit;
}

if (isset($_POST['_logout_']) && isset($_SERVER['HTTP_REFERER']))
    $_SESSION['REAL_REFERER'] = $_SERVER['HTTP_REFERER'];

if (
    isset($_SESSION['REAL_REFERER']) &&
    $_SESSION['REAL_REFERER'] != ""
  )
  $referer = $_SESSION['REAL_REFERER'];
elseif (isset($_POST['referer']) && $_POST['referer'] != "")
  $referer = $_POST['referer'];
else
  $referer = "./";

// Send 401 error so browser may clear any HTTP basic authentication credentials
header('HTTP/1.1 401 Unauthorized');
header('WWW-Authenticate: WebForm');

// Send customized headers to disallow any access to FHEM backends
// can be used in HAproxy setups to remove access to FHEM based on Hoanoho session
header('X-FHEM-DisallowAdmin: ' . session_id());
header('X-FHEM-DisallowUser: ' . session_id());

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
                $_SESSION['mobile'] = false;

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

                header('Location: ' . $referer);
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
        $_SESSION['mobile'] = false;
        $_SESSION['quicklogin'] = $_GET['login'];
        $_SESSION['quicklogin_newsession'] = true;

        $sql = "UPDATE users set lastlogin = now() where uid = " . $row->uid;
        mysql_query($sql);

        header('Location: ' . $referer);
        exit;
    }
}
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <link rel="stylesheet" href="css/login.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - Anmelden</title>
    </head>
<body>
    <section class="loginform_main">
        <form class="loginform" action="login.php" method="post">
            <h1><span class="log-in">Anmelden</span></h1>
            <p class="float">
                <label for="login"><img src="img/user_small.png">Benutzername</label>
                <input type="text" name="login_username" placeholder="Benutzername" autofocus>
            </p>
            <p class="float">
                <label for="password"><img src="img/password_small.gif">Passwort</label>
                <input type="password" name="login_password" placeholder="Passwort">
            </p>
            <p class="clearfix">
                <input type="submit" name="submit" value="Anmelden">
            </p>
            <input type="hidden" name="cmd" value="login">
            <input type="hidden" name="referer" value="<?php echo $referer; ?>">
        </form>​​
    </section>

    <?php if (isset($__CONFIG['maintenance_msg']) && $__CONFIG['maintenance_msg'] != "") { ?>
    <section class="loginform_main loginform maintenance_msg">
      <h2>Systemnachricht</h2>
      <p>
        <?php echo $__CONFIG['maintenance_msg'] ?>
      </p>
    </section>
    <?php } ?>
<?php if ($__CONFIG['php_debugbar'] == "1" && is_object($debugbar)) { echo $debugbarRenderer->render(); } ?>
</body>
</html>
