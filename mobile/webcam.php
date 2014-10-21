<?php
    include dirname(__FILE__).'/../includes/dbconnection.php';
    include dirname(__FILE__).'/../includes/sessionhandler.php';
    include dirname(__FILE__).'/../includes/getConfiguration.php';
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <?php include dirname(__FILE__).'/includes/getConfiguration.php'; ?>

        <link rel="stylesheet" href="./css/ratchet.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <script src="./js/ratchet.js"></script>
        <script src="./js/standalone.js"></script>

        <title><?php echo $__CONFIG['main_sitetitle'] . " - Webcams"; ?></title>
    </head>
    <body>
        <header class="bar-title">
            <h1 class="title">Webcams</h1>
        </header>

        <div class="content">
            <ul class="list">
                <?php
                    $sql = "SELECT devices.dev_id, devices.name, rooms.name roomname, rooms.room_id FROM devices join device_types on device_types.dtype_id = devices.dtype_id join types on types.type_id = device_types.type_id left join rooms on rooms.room_id = devices.room_id where types.name = 'Webcam'";
                    $result = mysql_query($sql);
                    while ($device = mysql_fetch_object($result)) {
                        echo "<li><a href=\"device.php?room=".$device->room_id."&device=".$device->dev_id."&prevsite=webcam\" data-transition=\"slide-in\">".$device->name." [".$device->roomname."]</a>";
                          echo "<span class=\"chevron\"></span></li>";
                    }
                ?>
            </ul>
            <br><br><br>
        </div>

        <?php include "includes/nav.php"; ?>
    </body>
</html>
