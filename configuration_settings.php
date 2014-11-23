 <?php
    require_once dirname(__FILE__).'/includes/sessionhandler.php';

	if ($_SESSION['isAdmin'] != 1) {
		header('HTTP/1.1 403 Forbidden');
		header('Location: ./');
		exit;
	}

    function displayValue($object)
    {
        // handling for special chars
        $object->value = htmlspecialchars($object->value);

        	switch ($object->type )
    		{
    			default:
    			case"text":
    				echo "<input type=\"text\" name=\"".$object->configstring."\" value=\"".$object->value."\" placeholder=\"".$object->hint."\" >";
    			break;
    			case"boolean":
    				echo "<select name=\"".$object->configstring."\">";
    				echo "<option ".($object->value == "1" ? "selected" : "")." value=\"1\">Ja</option>";
    				echo "<option ".($object->value == "0" ? "selected" : "")." value=\"0\">Nein</option>";
    				echo "</select>";
    			break;
    			case"password":
    				echo "<input type=\"password\" name=\"".$object->configstring."\" value=\"".$object->value."\">";
    			break;
          case"dwd_region":
    				echo "<select name=\"".$object->configstring."\" style='width:200px'>";
    				echo "<option ".($object->value == "" ? "selected" : "")." value=\"\">-</option>";
            $dwd = "SELECT warngebiet_kurz,warngebiet_dwd_kennung FROM dwd_warngebiet WHERE typ_id != '3' ORDER BY warngebiet_kreis_stadt_name ASC, warngebiet_dwd_kennung DESC;";
            $dwdresult = mysql_query($dwd);
            while ($dwd_regions = mysql_fetch_object($dwdresult)) {
    				  echo "<option ".($object->value == $dwd_regions->warngebiet_dwd_kennung ? "selected" : "")." value=\"".$dwd_regions->warngebiet_dwd_kennung."\">".$dwd_regions->warngebiet_kurz." (".$dwd_regions->warngebiet_dwd_kennung.")</option>";
            }
    				echo "</select>";
    			break;
    		}
    }

    if (isset($_POST['cmd']) && $_POST['cmd'] == "savesettings") {

        foreach ($_POST as $key => $value) {
            if($key == "cmd" || $key == "submit")
                continue;

            // handling for special chars
            $value = htmlspecialchars_decode($value);

            $sql = "update configuration set value = '".$value."' where configstring = '".$key."'; ";
            mysql_query($sql);
        }
    }
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/configuration.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/nav.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <?php require_once dirname(__FILE__).'/includes/getUserSettings.php'; ?>

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - Einstellungen - Parameter</title>
    </head>
<body>
    <?php require_once dirname(__FILE__).'/includes/nav.php'; ?>

    <?php
    if (isset($_GET['showall']) && $_GET['showall'] == "true") {
      $sql2 = "SELECT distinct category FROM configuration where dev_id = 0 ORDER BY category ASC";
    } else {
      $sql2 = "SELECT distinct category FROM configuration where dev_id = 0 AND visible = 1 ORDER BY category ASC";
    }
    $result2 = mysql_query($sql2);
    while ($category = mysql_fetch_object($result2)) {
    ?>
        <section class="main_configuration_settings">
            <h1><span><?php echo $category->category; ?></span></h1>

            <div id="header">
                <div id="text">Name</div>
                <div id="value">Einstellung</div>
            </div>
            <form method="POST" enctype="multipart/form-data" name="configForm<?php echo $category->category; ?>" id="configForm">
            <?php
            $sql = "SELECT * FROM configuration where dev_id = 0 and category = '".$category->category."' ORDER BY configstring ASC";
            $result = mysql_query($sql);
            while ($config = mysql_fetch_object($result)) {
            ?>
                    <div id="listitem">
                        <div id="text"><?php echo $config->title; ?>:</div>
                        <div id="value"><?php displayValue($config); ?></div>
                    </div>
            <?php
            }
            ?>
            <input type="hidden" name="cmd" value="savesettings">
            <div id="submit"><input type="reset" id="greybutton" name="resetbtn" value="Zurücksetzen">&nbsp;&nbsp;&nbsp;<input type="submit" id="greenbutton" name="submit" value="Speichern"></div>
            </form>
        </section>
    <?php
    }
    ?>

	<?php if (isset($__CONFIG['HOANOHO_VERSION']) || isset($__CONFIG['HSE_VERSION']) || isset($__CONFIG['HSE_ENV']) || isset($__CONFIG['HOANOHO_BUILDNAME'])) { ?>
    <section class="main_configuration_settings">
        <h1><span>Installationsdetails</span></h1>

		<?php if (isset($__CONFIG['HOANOHO_VERSION'])) { ?>
		<div id="listitem">
		   <div id="text">Hoanoho Version:</div>
		   <div id="value"><?php echo $__CONFIG['HOANOHO_VERSION'] ?></div>
		</div>
		<?php } ?>

		<?php if (isset($__CONFIG['HSE_VERSION'])) { ?>
		<div id="listitem">
		   <div id="text">HSE Version:</div>
		   <div id="value"><?php echo $__CONFIG['HSE_VERSION'] ?></div>
		</div>
		<?php } ?>

		<?php if (isset($__CONFIG['HSE_ENV'])) { ?>
		<div id="listitem">
		   <div id="text">HSE Environment:</div>
		   <div id="value"><?php echo $__CONFIG['HSE_ENV'] ?></div>
		</div>
		<?php } ?>

		<?php if (isset($__CONFIG['HOANOHO_BUILDNAME'])) { ?>
		<div id="listitem">
		   <div id="text">Base System Build:</div>
		   <div id="value"><?php echo $__CONFIG['HOANOHO_BUILDNAME'] ?></div>
		</div>
		<?php } ?>

    </section>
	<?php } ?>
<?php if ($__CONFIG['php_debugbar'] == "1" && is_object($debugbar)) { echo $debugbarRenderer->render(); } ?>
</body>
</html>
