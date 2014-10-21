<?php
    include dirname(__FILE__).'/../includes/dbconnection.php';
    include dirname(__FILE__).'/../includes/sessionhandler.php';
    include dirname(__FILE__).'/../includes/getConfiguration.php';

    if(!isset($_GET['room']))
        header('Location: ./mobile/');

    $sql = "SELECT * FROM rooms where room_id = " . $_GET['room'];
    $result = mysql_query($sql);
    $room = mysql_fetch_object($result);
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <?php include dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <link rel="stylesheet" href="./css/ratchet.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <script src="./js/ratchet.js"></script>
        <script src="./js/standalone.js"></script>

        <title><?php echo $__CONFIG['main_sitetitle'] . " - " . $room->name; ?></title>
    </head>
    <body>
        <header class="bar-title">
            <a class="button-prev" href="automation.php" data-transition="slide-out">Zurück</a>
            <h1 class="title"><?php echo $room->name; ?></h1>
        </header>

        <div class="content">
            <ul class="list">
                <?php
                    $sql = "SELECT * FROM devices where room_id = " . $_GET['room'] . " and devices.isHidden != 'on'";
                    $result = mysql_query($sql);
                    while ($device = mysql_fetch_object($result)) {
                        echo "<li><a href=\"device.php?room=".$room->room_id."&device=".$device->dev_id."\" data-transition=\"slide-in\" data-ignore=\"push\">".$device->name."</a>";
                          echo "<span class=\"chevron\"></span></li>";
                    }
                ?>
            </ul>
            <br><br><br>
        </div>

        <?php include "includes/nav.php"; ?>
    </body>
</html>
