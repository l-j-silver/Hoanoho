<?php
require_once dirname(__FILE__).'/../includes/dbconnection.php';
require_once dirname(__FILE__).'/../includes/sessionhandler.php';
require_once dirname(__FILE__).'/../includes/getConfiguration.php';

$url_type_name = 'fhem_url_'.$_GET['type'];
if (isset($_GET['type']) && isset($__CONFIG[$url_type_name])) {
  $url = $__CONFIG[$url_type_name];
} else {
  $url = $__CONFIG['fhem_url_mobile'];
}
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <link rel="stylesheet" href="css/ratchet.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/fwrapper.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/cookie.js"></script>

        <script src="js/ratchet.js"></script>
        <script src="js/standalone.js"></script>

        <script language="javascript">
            function redirectToURL(URL)
            {
                window.location.href = URL;
            }
        </script>
        <script type="text/javascript">
          function resizeIframe(obj){
            { obj.style.height = 0; };
            { obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px'; }
            { obj.style.width = 0; };
            { obj.style.width = obj.contentWindow.document.body.scrollWidth + 'px'; }
          }
         </script>

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - FHEM</title>
    </head>
    <body>
        <header class="bar-title">
            <h1 class="title">FHEM</h1>
        </header>

        <div class="content">
                <iframe id="fhem" src="<?php echo $url ?>" onload='javascript:resizeIframe(this);'></iframe>
        </div>
        <?php require_once "includes/nav.php"; ?>
    </body>
</html>
