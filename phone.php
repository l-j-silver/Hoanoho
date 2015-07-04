<?php
    require_once dirname(__FILE__).'/includes/sessionhandler.php';
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/phone.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/nav.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <?php require_once dirname(__FILE__).'/includes/getUserSettings.php'; ?>

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <title><?php echo $__CONFIG['main_sitetitle'] ?> - Telefon</title>
    </head>
<body>
    <?php require_once dirname(__FILE__).'/includes/nav.php'; ?>

    <section class="main_telephone">
        <h1><span>Anrufliste</span></h1>
        <div id="legend">
            <div id="item"><img src="img/telefon_1.png"></div><div>Eingehend geführt</div>
            <div id="item"><img src="img/telefon_4.png"></div><div>Abgehend geführt</div>
            <div id="item"><img src="img/telefon_2.png"></div><div>Anruf verpasst</div>
            <div id="item"><img src="img/telefon_3.png"></div><div>Anruf abgelehnt</div>
            <div id="clear">&nbsp;</div>
        </div>
        <div id="headline">
            <div id="icon"></div>
            <div id="date">Datum</div>
            <div id="number">Anrufer</div>
            <div id="line">Nebenstelle</div>
            <div id="period">Dauer</div>
            <div id="action">&nbsp;</div>
        </div>
        <?php
        $sql = "SELECT typ,DATE_FORMAT(date, '%d.%m.%Y %H:%i') as date,name,rufnummer,nebenstelle,dauer from callerlist";
        $result = mysql_query($sql);

        $i=0;
        while ($row = mysql_fetch_object($result)) {
        ?>
        <div id="entry">
            <?php echo "<div id=\"icon\"><img src=\"./img/telefon_" . $row->typ . ".png\"></div>"; ?>
            <div id="date"><?php echo $row->date; ?></div>
            <div id="number"><?php if(strlen($row->name) > 0) echo '<a href="tel:'.$row->rufnummer.'">'.$row->name.'</a>'; else echo '<a href="tel:'.$row->rufnummer.'">'.$row->rufnummer.'</a>'; ?></div>
            <div id="line"><?php echo $row->nebenstelle; ?></div>
            <div id="period"><?php echo $row->dauer; ?></div>
            <div id="action">&nbsp;</div>
        </div>
        <?php $i++; } ?>
    </section>
<?php if ($__CONFIG['php_debugbar'] == "1" && is_object($debugbar)) { echo $debugbarRenderer->render(); } ?>
</body>
</html>
