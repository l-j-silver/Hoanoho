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
?>

<html>
  <head>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
		<script type="text/javascript" src="js/cookie.js"></script>

    <script type="text/javascript">
      function resizeIframe(obj){
        { obj.style.height = 0; };
        { obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px'; }
        { obj.style.width = 0; };
        { obj.style.width = obj.contentWindow.document.body.scrollWidth + 'px'; }

        <?php if ($url != $__CONFIG['fhem_url_admin']) { ?>
        var doc = obj.contentDocument || obj.contentWindow.document;
        var style = doc.createElement('style');

        style.textContent = "#logo, #hdr { display: none; } ";
        style.textContent += "#menu { top:0px; } ";
        style.textContent += "#content, #menu, #right { height: 660px; height: -moz-calc(100vh); height: -webkit-calc(100vh); height: -o-calc(100vh); height: calc(100vh); }";
        style.textContent += "body { background-image:none; }";

        doc.head.appendChild(style);
        <?php } ?>
      }
     </script>

      <meta charset="UTF-8" />

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
      <div id="fhem"><iframe id="fhemframe" src="<?php echo $url ?>" onload='javascript:resizeIframe(this);'></iframe></div>
    </section>
</body>
</html>
