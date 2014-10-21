<link rel="shortcut icon" type="image/x-icon" href="../img/favicons/favicon.ico">

<link rel="icon" type="image/png" href="../img/favicons/favicon-16x16.png" sizes="16x16">
<link rel="icon" type="image/png" href="../img/favicons/favicon-32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="../img/favicons/favicon-96x96.png" sizes="96x96">
<link rel="icon" type="image/png" href="../img/favicons/favicon-160x160.png" sizes="160x160">
<link rel="icon" type="image/png" href="../img/favicons/favicon-192x192.png" sizes="192x192">

<link rel="apple-touch-icon" sizes="57x57" href="../img/favicons/apple-touch-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="../img/favicons/apple-touch-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="../img/favicons/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="../img/favicons/apple-touch-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="../img/favicons/apple-touch-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="../img/favicons/apple-touch-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="../img/favicons/apple-touch-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="../img/favicons/apple-touch-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="../img/favicons/apple-touch-icon-180x180.png">

<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no">

<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Hoanoho">
<meta name="application-name" content="Hoanoho">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<meta name="msapplication-TileColor" content="#603cba">
<meta name="msapplication-TileImage" content="../img/favicons/mstile-144x144.png">
<meta name="msapplication-config" content="../img/favicons/browserconfig2.xml">


<script type="text/javascript">
if(("standalone" in window.navigator) && window.navigator.standalone){
  var noddy, remotes = false;

  document.addEventListener('click', function(event) {
    noddy = event.target;

    while(noddy.nodeName !== "A" && noddy.nodeName !== "HTML") {
      noddy = noddy.parentNode;
    }

    if('href' in noddy && noddy.href.indexOf('http') !== -1 && (noddy.href.indexOf(document.location.host) !== -1 || remotes))
    {
      event.preventDefault();
      document.location.href = noddy.href;
    }

  },false);
}
</script>