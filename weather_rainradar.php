<?php
    require_once dirname(__FILE__).'/includes/sessionhandler.php';
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/weather.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/nav.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <?php require_once dirname(__FILE__).'/includes/getUserSettings.php'; ?>

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - Regenradar</title>
    </head>
<body>
    <?php require_once dirname(__FILE__).'/includes/nav.php'; ?>

    <section class="main_weather">
        <h1><span>Deutschland</span></h1>
            <div id="radar"><a href="http://www.wettergefahren.de/wetter/deutschland/aktuell.html" target="_blank"><img src="http://www.wettergefahren.de/wundk/radar/Radarfilm_WEB_DL.gif"></a></div>
            <div id="legend"><img src="img/weather_radar_legend.png"></div>
    </section>

    <?php
    if ($__CONFIG['dwd_region'] != "") {
      $dwd = "SELECT dwd_warngebiet.region_id, dwd_region.region_name, dwd_region.karten_region
              FROM dwd_warngebiet
              INNER JOIN dwd_region
              ON dwd_warngebiet.region_id=dwd_region.region_id
              WHERE dwd_warngebiet.warngebiet_dwd_kennung = '".$__CONFIG['dwd_region']."' LIMIT 1;";
      $dwdresult = mysql_query($dwd);
      $dwdregion = mysql_fetch_object($dwdresult);

      if (isset($dwdregion->karten_region)) {
      ?>

      <section class="main_weather">
          <h1><span><?php echo $dwdregion->region_name ?></span></h1>
          <div id="radar"><a href="http://www.wettergefahren.de/wetter/region/<?php echo strtolower($dwdregion->karten_region) ?>/aktuell.html" target="_blank"><img src="http://www.wettergefahren.de/wundk/radar/Webradar_<?php echo $dwdregion->karten_region ?>.jpg"></a></div>
          <div id="legend"><img src="img/weather_radar_legend.png"></div>
      </section>

    <?php
      }
    }
    ?>

<?php if ($__CONFIG['php_debugbar'] == "1" && is_object($debugbar)) { echo $debugbarRenderer->render(); } ?>
</body>
</html>
