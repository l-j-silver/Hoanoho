<?php
    require_once dirname(__FILE__).'/includes/sessionhandler.php';
    require_once dirname(__FILE__).'/includes/dwd_parser.php';
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/weather.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/nav.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <?php require_once dirname(__FILE__).'/includes/getUserSettings.php'; ?>

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - Wetterwarnungen</title>
    </head>
<body>
    <?php require_once dirname(__FILE__).'/includes/nav.php'; ?>

    <section class="main_weather">
        <h1><span>Wetter (Unwetter-) Warnungen</span></h1>
            <?php
            if ($__CONFIG['dwd_region'] != "") {

              $dwd = "SELECT dwd_warngebiet.region_id, dwd_region.region_name, dwd_region.karten_region
                      FROM dwd_warngebiet
                      INNER JOIN dwd_region
                      ON dwd_warngebiet.region_id=dwd_region.region_id
                      WHERE dwd_warngebiet.warngebiet_dwd_kennung = '".$__CONFIG['dwd_region']."' LIMIT 1;";
              $dwdresult = mysql_query($dwd);
              $dwdregion = mysql_fetch_object($dwdresult);

            ?>

            <div id="radar"><a href="http://www.wettergefahren.de/app/ws/index.jsp?view=map&land_code=<?= $dwdregion->region_id ?>&height=x&warn_type=0" target="_blank"><img src="http://www.wettergefahren.de/dyn/app/ws/maps/<?= $dwdregion->region_id ?>_x_x_0.gif"></a></div>
            <div id="legend"><img src="img/weather_warning_legend.gif"></div>

            <?php
            }

            if (stripos($dwd_warnung_kurz, "Es liegt aktuell keine Warnung") !== FALSE) {
            ?>
            <div id="title">Warnmeldung</div>
            <?php } ?>
            <div id="warnung"><?php echo $dwd_warnung; ?></div>

            <?php
            if (isset($dwd_region_report_warning) && $dwd_region_report_warning != "") {
            ?>
            <div id="title">Warnlagebericht</div>
            <div id="text"><?php echo $dwd_region_report_warning; ?></div>
            <div id="source">Quelle: Deutscher Wetterdienst</div>
            <?php
            }
            ?>
    </section>
<?php if ($__CONFIG['php_debugbar'] == "1" && is_object($debugbar)) { echo $debugbarRenderer->render(); } ?>
</body>
</html>