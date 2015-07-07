<?php

require_once dirname(__FILE__).'/includes/sessionhandler.php';
require_once dirname(__FILE__).'/includes/dwd_parser.php';

function getCurrentOpenWeatherMapData($in_arr)
{
    $sql = "select * from openweathermap where measuredate = (select measuredate from openweathermap group by measuredate order by measuredate desc limit 1)";
    $weather_result = mysql_query($sql);
    while ($row = mysql_fetch_object($weather_result)) {
      if (isset($row->weatherkey) && $row->weatherkey != "") {
        if (isset($row->weathervalue) && $row->weathervalue != "") {

          // add wind direction
          if ($row->weatherkey == 'wind.deg') {
              if ($row->weathervalue < 22.5) {
                $wdir = "Nord";
              } elseif ($row->weathervalue < 45) {
                $wdir = "Nord-Nordost";
              } elseif ($row->weathervalue < 67.5) {
                $wdir = "Nord-Ost";
              } elseif ($row->weathervalue < 90) {
                $wdir = "Ost";
              } elseif ($row->weathervalue < 112.5) {
                $wdir = "Ost-Südost";
              } elseif ($row->weathervalue < 135) {
                $wdir = "Südost";
              } elseif ($row->weathervalue < 157.5) {
                $wdir = "Süd-Südost";
              } elseif ($row->weathervalue < 180) {
                $wdir = "Süd";
              } elseif ($row->weathervalue < 202.5) {
                $wdir = "Süd-Südwest";
              } elseif ($row->weathervalue < 225) {
                $wdir = "Südwest";
              } elseif ($row->weathervalue < 247.5) {
                $wdir = "West-Südwest";
              } elseif ($row->weathervalue < 270) {
                $wdir = "West";
              } elseif ($row->weathervalue < 292.5) {
                $wdir = "West-Nordwest";
              } elseif ($row->weathervalue < 315) {
                $wdir = "Nordwest";
              } elseif ($row->weathervalue < 337.5) {
                $wdir = "Nord-Nordwest";
              } elseif ($row->weathervalue < 361) {
                $wdir = "Nord";
              }

              $in_arr['wind.dir'] = $wdir;
          }

          $in_arr[$row->weatherkey] = $row->weathervalue;

        } else {
          $in_arr[$row->weatherkey] = "-";
        }
      }
    }

    return $in_arr;
}

function getForecastOpenWeatherMapData($days)
{
    $forecast = array();

    for ($i=0; $i < $days; $i++) {
        $forecast_day = array();

        $sql = "select * from openweathermap_forecast where weatherkey like 'list.".$i.".%'";
        $weather_result = mysql_query($sql);
        while ($row = mysql_fetch_object($weather_result)) {
          if (isset($row->weatherkey) && $row->weatherkey != "") {
            if (isset($row->weathervalue) && $row->weathervalue != "") {

              $explode = explode(".", $row->weatherkey);
              // add wind direction
              if ($explode[2] == 'deg') {
                if ($row->weathervalue < 22.5) {
                  $wdir = "Nord";
                } elseif ($row->weathervalue < 45) {
                  $wdir = "Nord-Nordost";
                } elseif ($row->weathervalue < 67.5) {
                  $wdir = "Nord-Ost";
                } elseif ($row->weathervalue < 90) {
                  $wdir = "Ost";
                } elseif ($row->weathervalue < 112.5) {
                  $wdir = "Ost-Südost";
                } elseif ($row->weathervalue < 135) {
                  $wdir = "Südost";
                } elseif ($row->weathervalue < 157.5) {
                  $wdir = "Süd-Südost";
                } elseif ($row->weathervalue < 180) {
                  $wdir = "Süd";
                } elseif ($row->weathervalue < 202.5) {
                  $wdir = "Süd-Südwest";
                } elseif ($row->weathervalue < 225) {
                  $wdir = "Südwest";
                } elseif ($row->weathervalue < 247.5) {
                  $wdir = "West-Südwest";
                } elseif ($row->weathervalue < 270) {
                  $wdir = "West";
                } elseif ($row->weathervalue < 292.5) {
                  $wdir = "West-Nordwest";
                } elseif ($row->weathervalue < 315) {
                  $wdir = "Nordwest";
                } elseif ($row->weathervalue < 337.5) {
                  $wdir = "Nord-Nordwest";
                } elseif ($row->weathervalue < 361) {
                  $wdir = "Nord";
                }

                $forecast_day[$explode[0].".".$explode[1].'.dir'] = $wdir;
              }

              $forecast_day[$row->weatherkey] = $row->weathervalue;

            } else {
              $forecast_day[$row->weatherkey] = "-";
            }
          }
        }

        $forecast[] = $forecast_day;
    }

    return $forecast;
}

function getCurrentWeatherDataFromLocalStation($in_arr)
{
    $sql = "select timestamp_unix, valuename, value, valueunit from device_data where timestamp = (select max(timestamp) from device_data where deviceident = 'wslogger') and deviceident = 'wslogger'";
    $result = mysql_query($sql);

    if(mysql_num_rows($result) > 0)
        $in_arr['ws_available'] = true;

    while ($item = mysql_fetch_object($result)) {
        $in_arr['ws_'.$item->valuename] = $item->value;
    }

    return $in_arr;
}

$day = date("w");
$days_relative = array("Heute","Morgen","Übermorgen");

switch ($day) {
    case '0':
        $days = array("Sonntag","Montag","Dienstag");
        break;

    case '1':
        $days = array("Montag","Dienstag","Mittwoch");
        break;

    case '2':
        $days = array("Dienstag","Mittwoch","Donnerstag");
        break;

    case '3':
        $days = array("Mittwoch","Donnerstag","Freitag");
        break;

    case '4':
        $days = array("Donnerstag","Freitag","Samstag");
        break;

    case '5':
        $days = array("Freitag","Samstag","Sonntag");
        break;

    case '6':
        $days = array("Samstag","Sonntag","Montag");
        break;

    default:
        $days = array("unbekannt","unbekannt","unbekannt");
        break;
}

?>

<html>
    <head>
        <meta charset="UTF-8" />

        <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/weather.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/nav.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <?php require_once dirname(__FILE__).'/includes/getUserSettings.php'; ?>

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - Wetterübersicht</title>
    </head>
<body>
    <?php require_once dirname(__FILE__).'/includes/nav.php'; ?>

    <?php
    $weather = array();
    $weather['ws_available'] = false;

    $weather = array_merge($weather, getCurrentOpenWeatherMapData($weather));
    $weather = array_merge($weather, getCurrentWeatherDataFromLocalStation($weather));

    if (count($weather) > 1) {
        $sunrise = date('H:i',$weather['sys.sunrise']);
        $sunset = date('H:i',$weather['sys.sunset']);
        $time = date('H:i',$weather['dt']);
    ?>

    <section class="main_weather">
        <h1><span>Aktuelle Wetterlage</span></h1>
            <div id="weathericon"><img src="<?php echo "./img/weather/openweathermap/".$weather['weather.0.icon'].".png"; ?>"></div>
            <div id="details">
                <div><?php echo $weather['weather.0.description']."; ".(isset($dwd_region_report0) ? $dwd_region_report0 : ""); ?></div>
                <div>&nbsp;</div>
                <div><b>Stadt:</b> <?php echo $weather['name']; ?></div>
                <div><b>Uhrzeit:</b> <?php echo $time ." Uhr";  ?></div>
                <div><b>Temperatur:</b> <?php echo ($weather['ws_available'] == true ? $weather['ws_OT']."°C  (".$weather['ws_WC']." °C gefühlt)" : $weather['main.temp']." °C"); ?></div>
                <div>&nbsp;</div>

                <?php if ($weather['ws_available'] == true) {?>
                <div><b>Regenmenge pro Stunde:</b> <?php echo $weather['ws_Rain1h']; ?> l/qm</div>
                <div><b>Regenmenge pro Tag:</b> <?php echo $weather['ws_Rain24h']; ?> l/qm</div>
                <?php } else { ?>
                <div><b>Regenmenge:</b> <?php echo (isset($weather['rain.3h']) ? $weather['rain.3h'] : "0") ?> l/qm</div>
                <?php } ?>

                <div><b>Bewölkung:</b> <?php echo $weather['clouds.all']; ?> %</div>
                <div><b>Luftfeuchtigkeit:</b> <?php echo ($weather['ws_available'] == true ? $weather['ws_OH'] : $weather['main.humidity']) ?> %</div>
                <div><b>Luftdruck:</b> <?php echo ($weather['ws_available'] == true ? $weather['ws_P'] : $weather['main.pressure']); ?> hPa</div>
                <div>&nbsp;</div>
                <div><b>Windgeschwindigkeit:</b> <?php echo ($weather['ws_available'] == true ? $weather['ws_Wind'] : $weather['wind.speed']); ?> km/h</div>
                <div><b>Windrichtung:</b> <?php echo ($weather['ws_available'] == true ? $weather['ws_WindDir'] : $weather['wind.dir']); ?></div>
                <div>&nbsp;</div>
                <div><b>Sonnenaufgang:</b> <?php echo $sunrise ." Uhr"; ?></div>
                <div><b>Sonnenuntergang:</b> <?php echo $sunset." Uhr"; ?></div>
                <div>&nbsp;</div>
            </div>
            <?php if (isset($dwd_warnung_kurz) && stripos($dwd_warnung_kurz, "Es liegt aktuell keine Warnung") === FALSE) { ?>
              <div id="title" style="text-align:center">Warnmeldung</div>
              <div id="warnung" style="text-align:center"><a href="weather_warning.php"><?php echo $dwd_warnung_kurz; ?></a></div>
            <?php } ?>
            <div id="footer"></div>
    </section>

    <?php
    }

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
          <div id="dwdimage"><a href="http://www.wettergefahren.de/wetter/region/<?php echo strtolower($dwdregion->karten_region) ?>/aktuell.html" target="_blank"><img src="http://www.wettergefahren.de/DWD/wetter/wv_allg/deutschland/bilder/<?php echo $dwdregion->karten_region ?>.jpg"></a></div>
      </section>

    <?php
      }
    }
    ?>

    <section class="main_weather">
        <h1><span>Deutschland</span></h1>
        <div id="dwdimage"><a href="http://www.wettergefahren.de/wetter/deutschland/aktuell.html" target="_blank"><img src="http://www.wettergefahren.de/DWD/wetter/wv_allg/deutschland/bilder/Deutschland.jpg"></a></div>
    </section>

    <?php
    $forecast_days = 3;
    $forecast = getForecastOpenWeatherMapData($forecast_days);
    $i = 0;

    for ($i=0; $i < $forecast_days; $i++) {
      if (is_array($forecast[$i]) && count($forecast[$i]) > 0) {
        echo "<section class=\"main_weather\">";
            echo "<h1><span>Vorhersage für " . $days_relative[$i] . " (".$days[$i].")</span></h1>";

            echo "<div id=\"weathericon\"><img src=\"./img/weather/openweathermap/".$forecast[$i]['list.'.$i.'.weather.0.icon'].".png\"></div>";
            echo "<div id=\"details\">";
                echo "<div>".$forecast[$i]['list.'.$i.'.weather.0.description']."</div>";
                echo "<div>&nbsp;</div>";
                echo "<div><b>Temperatur Morgens:</b> ".$forecast[$i]['list.'.$i.'.temp.morn']." °C</div>";
                echo "<div><b>Temperatur Tagsüber:</b> ".$forecast[$i]['list.'.$i.'.temp.day']." °C</div>";
                echo "<div><b>Temperatur Abends:</b> ".$forecast[$i]['list.'.$i.'.temp.eve']." °C</div>";
                echo "<div><b>Temperatur Nachts:</b> ".$forecast[$i]['list.'.$i.'.temp.night']." °C</div>";
                echo "<div>&nbsp;</div>";
                echo "<div><b>Temperatur Minimum:</b> ".$forecast[$i]['list.'.$i.'.temp.min']." °C</div>";
                echo "<div><b>Temperatur Maximum:</b> ".$forecast[$i]['list.'.$i.'.temp.max']." °C</div>";
                echo "<div>&nbsp;</div>";
                echo "<div><b>Regenmenge:</b> ".(isset($forecast[$i]['list.'.$i.'.rain']) ? $forecast[$i]['list.'.$i.'.rain'] : "0")." l/qm.</div>";
                print("<div><b>Bewölkung:</b> ".$forecast[$i]['list.'.$i.'.clouds']." %</div>");
                print("<div><b>Luftfeuchtigkeit:</b> ".$forecast[$i]['list.'.$i.'.humidity']." %</div>");
                print("<div><b>Luftdruck:</b> ".$forecast[$i]['list.'.$i.'.pressure']." hPa</div>");
                print("<div>&nbsp;</div>");
                print("<div><b>Windgeschwindigkeit:</b> ".$forecast[$i]['list.'.$i.'.speed']." km/h</div>");
                print("<div><b>Windrichtung:</b> ".$forecast[$i]['list.'.$i.'.dir']."</div>");
                print("<div>&nbsp;</div>");
            echo "</div>";
            if (isset($dwd_region_report)) {
                echo "<div id=\"title\">Bericht</div>";
                echo "<div id=\"text\">".$dwd_region_report[$i]."</div>";
                echo "<div id=\"source\">Quelle: Deutscher Wetterdienst</div>";
            }
            echo "<div id=\"footer\"></div>";
        echo "</section>";
      }
    }
    ?>
</body>
</html>
