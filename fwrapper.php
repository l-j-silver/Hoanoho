<?php
  require_once dirname(__FILE__).'/includes/dbconnection.php';
  require_once dirname(__FILE__).'/includes/sessionhandler.php';
  require_once dirname(__FILE__).'/includes/getConfiguration.php';

  if (isset($_GET['type']))
    $url_type_name = 'fhem_url_'.$_GET['type'];

  if (isset($url_type_name) && isset($__CONFIG[$url_type_name]))
    $url = $__CONFIG[$url_type_name];
  else
    $url = $__CONFIG['fhem_url_web'];

  if ($url == $__CONFIG['fhem_url_admin'] && $_SESSION['isAdmin'] != 1) {
	header('HTTP/1.1 403 Forbidden');
    header('Location: ./');
    exit;
  }

  // Send customized headers to allow any access to FHEM backends
  // can be used in HAproxy setups to allow access to FHEM based on Hoanoho session
  if ($_SESSION['isAdmin'] == 1)
    header('X-FHEM-AllowAdmin: '.session_name().'='. session_id());
  header('X-FHEM-AllowUser: '.session_name().'='. session_id());
  $_SESSION['fhem_auth'] = true;
?>

<html>
  <head>
    <meta charset="UTF-8"; />
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
		<script type="text/javascript" src="js/cookie.js"></script>

    <script type="text/javascript">
      function resizeIframe(obj){
        var doc = obj.contentDocument || obj.contentWindow.document;

        <?php if ($url != $__CONFIG['fhem_url_admin']) { ?>
        var style = doc.createElement('link');
        style.rel = "stylesheet";
        style.href = "../css/fhem.css";
        style.type = "text/css";
        style.media = "screen";
        doc.head.appendChild(style);
        <?php } ?>

        <?php if ($_SESSION['mobile'] === true) { ?>
        var jscript = doc.createElement('script');
        jscript.type = "text/javascript";
        jscript.src = "../js/fhem_mobile.js";
        doc.head.appendChild(jscript);
        <?php } ?>

        { obj.style.height = 0; };
        { obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px'; }
        { obj.style.width = 0; };
        { obj.style.width = obj.contentWindow.document.body.scrollWidth + 'px'; }
      }
     </script>

      <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
      <link rel="stylesheet" href="css/nav.css" type="text/css" media="screen" title="no title" charset="UTF-8">
      <link rel="stylesheet" href="css/fwrapper.css" type="text/css" media="screen" title="no title" charset="UTF-8">

      <?php require_once dirname(__FILE__).'/includes/getUserSettings.php'; ?>
      <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

      <title><?php echo $__CONFIG['main_sitetitle'] . " - FHEM" ?></title>
  </head>
<body>
    <?php require_once dirname(__FILE__).'/includes/nav.php'; ?>

    <section class="board">
      <div id="fhem"><iframe id="fhem" src="<?php echo $url ?>" onload='javascript:resizeIframe(this);'></iframe></div>
    </section>
</body>
</html>
