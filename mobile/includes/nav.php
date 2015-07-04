<?php
    // check automation configuration
    $sql_automation = "select * from device_floors where position > 0 order by position asc";
    $result_automation = mysql_query($sql_automation);

    // check webcam configuration
    $sql_webcam = "SELECT dev_id,devices.name from devices left join device_types on device_types.dtype_id = devices.dtype_id left join types on types.type_id = device_types.type_id where types.name = 'Webcam'";
    $result_webcam = mysql_query($sql_webcam);
?>

<nav class="bar-tab">
    <ul class="tab-inner">
        <li class="tab-item">
            <a href="index.php" data-ignore="push">
                <img class="tab-icon" src="../img/pinboard.png">
                <div class="tab-label">Pinnwand</div>
            </a>
        </li>
    <?php if (mysql_num_rows($result_automation) > 0) { ?>
        <li class="tab-item">
            <a href="automation.php" data-ignore="push">
                <img class="tab-icon" src="../img/home.png">
                <div class="tab-label">Steuerung</div>
            </a>
        </li>
    <?php } ?>
    <?php if (mysql_num_rows($result_webcam) > 0) { ?>
        <li class="tab-item">
            <a href="webcam.php">
                <img class="tab-icon" src="../img/webcam.png">
                <div class="tab-label">Webcams</div>
            </a>
        </li>
    <?php } ?>
        <li class="tab-item">
            <a href="weather.php">
                <img class="tab-icon" src="../img/weather.png">
                <div class="tab-label">Wetter</div>
            </a>
        </li>
    <?php if ($__CONFIG['fhem_url_mobile'] != "") { ?>
        <li class="tab-item">
            <a href="fwrapper.php">
                <img class="tab-icon svg" name="fhem" src="../img/fhem.svg">
                <div class="tab-label">FHEM</div>
            </a>
        </li>
    <?php } ?>
        <li class="tab-item">
			<form action="login.php" method="POST">
				<input class="tab-icon" type="image" src="../img/logout.png" alt="Abmelden" title="Abmelden"/>
				<input name="_logout_" type="hidden"/>
				<div class="tab-label">Abmelden</div>
			</form>
        </li>
    </ul>
</nav>

<!-- Replace all SVG images with inline SVG -->
<script type="text/javascript">  
    jQuery(document).ready(function() {
        /*
         * Replace all SVG images with inline SVG
         */
            jQuery('img.svg').each(function(){
                var $img = jQuery(this);
                var imgID = $img.attr('id');
                var imgClass = $img.attr('class');
                var imgURL = $img.attr('src');
        
                jQuery.get(imgURL, function(data) {
                    // Get the SVG tag, ignore the rest
                    var $svg = jQuery(data).find('svg');
        
                    // Add replaced image's ID to the new SVG
                    if(typeof imgID !== 'undefined') {
                        $svg = $svg.attr('id', imgID);
                    }
                    // Add replaced image's classes to the new SVG
                    if(typeof imgClass !== 'undefined') {
                        $svg = $svg.attr('class', imgClass+' replaced-svg');
                    }
                    
                    // Remove any invalid XML tags as per http://validator.w3.org
                    $svg = $svg.removeAttr('xmlns:a');
                    
                    // Replace image with new SVG
                    $img.replaceWith($svg);
                });

            });
    });
</script>
<?php if ($__CONFIG['php_debugbar'] == "1" && is_object($debugbar)) { echo $debugbarRenderer->render(); } ?>