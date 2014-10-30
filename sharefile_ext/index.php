<?php

session_set_cookie_params(
    time() + 3600,
    "/",
    $_SERVER["HTTP_HOST"],
    true,
    true
);
session_name('HOANOHOSHARESESSID');

if (!isset($_SESSION))
  session_start();

if (isset($_POST['filePassword']))
    $_SESSION['filePassword'] = md5($_POST['filePassword']);
else
    $_SESSION['filePassword'] = "";


require_once dirname(__FILE__)."/../includes/dbconnection.php";

function data_uri($content, $mime)
{
  $base64   = base64_encode($content);

  return ('data:' . $mime . ';base64,' . $base64);
}

function showFile()
{
    // try to get the file out
    $sql = "SELECT *, case when File_AccessPassword is not null then 1 else 0 end protected FROM sharedfiles WHERE Hash = '".$_GET['f']."' and File_ValidDate >= NOW()";
    $result = mysql_query($sql);
    $curr_file = mysql_fetch_assoc($result);

    $size = $curr_file['File_Size'];
    $type = $curr_file['File_Type'];
    $name = $curr_file['File_Name'];
    $content = $curr_file['File_Content'];
    $extension = $curr_file['File_Extension'];
    $counter = $curr_file['File_AccessCounter'];
    $sid = $curr_file['SID'];

    // log access
	HTTP_X_FORWARDED_FOR
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$clientaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else
		$clientaddress = $_SERVER['REMOTE_ADDR'];
    $sql = "INSERT INTO sharedfiles_accesslog SET accessdate = NOW(), accessip = '" . $clientaddress . "', useragent = '" . $_SERVER['HTTP_USER_AGENT'] . "', sid = " . $sid;
    mysql_query($sql);
    ?>

    <html>
        <head>
            <meta charset="UTF-8" />

            <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
            <link rel="apple-touch-icon" href="img/favicon.ico"/>
            <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
            <link rel="stylesheet" href="css/lightbox.css" media="screen"/>

            <script src="js/jquery-1.10.2.min.js"></script>
            <script src="js/lightbox-2.6.min.js"></script>

            <script type="text/javascript" src="syntaxhighlighter/scripts/shCore.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushJScript.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushAppleScript.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushBash.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushCpp.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushCSharp.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushCss.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushJava.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushJScript.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushPerl.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushPhp.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushPlain.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushPython.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushRuby.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushSql.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushVb.js"></script>
            <script type="text/javascript" src="syntaxhighlighter/scripts/shBrushXml.js"></script>
            <link href="syntaxhighlighter/styles/shCore.css" rel="stylesheet" type="text/css"/>
            <link href="syntaxhighlighter/styles/shThemeDefault.css" rel="stylesheet" type="text/css"/>

            <title>Bereitstellung von '<?php echo $name; ?>'</title>
        </head>
        <body>
            <section class="main">
                <br>
                <div id="details">
                    <div id="left"><div id="text">Dateiname:</div></div><div id="right"><div id="value"><?php echo $name; ?></div></div>
                    <div id="left"><div id="text">Dateityp:</div></div><div id="right"><div id="value"><?php echo $type; ?></div></div>
                    <?php if ($size != null) { ?>
                        <div id="left"><div id="text">Größe:</div></div><div id="right"><div id="value"><?php echo $size." Bytes"; ?></div></div>
                    <?php } ?>
                </div>
                <form method="POST" enctype="multipart/form-data" id="getFileForm" name="getFileForm" action="getFile.php">
                    <input type="hidden" id="f" name="f" value="<?php echo $_GET['f']; ?>">
                    <input type="hidden" id="filePassword" name="filePassword" value="<?php echo $_SESSION['filePassword']; ?>">
                </form>
                <?php
                if (!strstr($type, "application")) {
                ?>
                <div id="preview">
                    <?php
                    if (strstr($type, "image")) {
                        echo "<a href=\"getFile.php?f=".$_GET['f']."&p=".$_SESSION['filePassword']."\" data-lightbox=\"image-1\" title=\"" . $name . "\"><img src='" . data_uri($content, $type) . "'></a>";
                    } else {
                            if ($extension == "js") {
                                $brush = "brush: js";
                            } elseif ($extension == "php" || $extension == "php5") {
                                $brush = "brush: php";
                            } elseif ($extension == "sh") {
                                $brush = "brush: bash";
                            } elseif ($extension == "cs") {
                                $brush = "brush: csharp";
                            } elseif ($extension == "c") {
                                $brush = "brush: c";
                            } elseif ($extension == "cpp") {
                                $brush = "brush: cpp";
                            } elseif ($extension == "css") {
                                $brush = "brush: css";
                            } elseif ($extension == "diff") {
                                $brush = "brush: diff";
                            } elseif ($extension == "java") {
                                $brush = "brush: java";
                            } elseif ($extension == "pl") {
                                $brush = "brush: perl";
                            } elseif ($extension == "py") {
                                $brush = "brush: python";
                            } elseif ($extension == "rb") {
                                $brush = "brush: ruby";
                            } elseif ($extension == "sql") {
                                $brush = "brush: sql";
                            } elseif ($extension == "txt" || $extension == "text") {
                                $brush = "brush: plain";
                            } elseif ($extension == "vb") {
                                $brush = "brush: vb";
                            } elseif ($extension == "xml") {
                                $brush = "brush: xml";
                            } else {
                                $brush = "brush: plain";
                            }

                            echo "<script type=\"syntaxhighlighter\" class=\"".$brush."\">";
                                    echo htmlspecialchars($content);
                            echo "</script>";
                            echo "<script type=\"text/javascript\">SyntaxHighlighter.all()</script>";
                    }
                }
                ?>
                </div>
                <div id="button">
                    <input type="submit" id="downloadbutton" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Datei speichern" onClick="getFileForm.submit();">
                </div>
            </section>
        </body>
    </html>
    <?php
}

if (isset($_GET['f'])) {
    // try to get the file out
    $sql = "SELECT File_Name, File_AccessPassword, case when File_AccessPassword is not null then 1 else 0 end protected FROM sharedfiles WHERE Hash = '".$_GET['f']."' and File_ValidDate >= NOW()";
    $result = mysql_query($sql);
    $curr_file = mysql_fetch_assoc($result);

    // if the query was invalid or failed to return a result, an generous message is shown
    if (!$result || !mysql_num_rows($result)) {
        exit;
    }

    // if file is protected by password
    if ($curr_file['protected'] == 1 && !isset($_SESSION['filePassword'])) {
        // ask for password and redirect for check
        ?>
        <html>
        <head>
            <meta charset="UTF-8" />

            <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
            <link rel="apple-touch-icon" href="img/favicon.ico"/>
            <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" />
            <link rel="stylesheet" href="css/lightbox.css" media="screen"/>

            <title>Bereitstellung von '<?php echo $curr_file['File_Name']; ?>'</title>
        </head>
        <html>
            <body>
                <section class="main_password">
                    <br>
                    <div id="passwordicon">&nbsp;</div>
                    <div id="passwordintro">Diese Datei wird durch ein Passwort geschützt!</div>
                    <form method="POST" enctype="multipart/form-data" id="filePasswordForm" name="filePasswordForm" >
                        <div id="passwordfield">
                            <input type="password" id="filePassword" name="filePassword" placeholder="hier Passwort eingeben" autofocus>
                        </div>
                        <div id="button">
                            <input type="submit" id="passwordbutton" value="Weiter ..." onClick="filePasswordForm.submit();">
                        </div>
                    </form>
                </section>
            </body>
        </html>
        <?php
    } elseif ($curr_file['protected'] == 1 && isset($_SESSION['filePassword'])) {
        // check password
        if ($curr_file['File_AccessPassword'] != $_SESSION['filePassword']) {
            session_destroy();
            header('Location: ./?f='.$_GET['f']);
        } else
            showFile();
    } else {
        showFile();
    }
}

?>
