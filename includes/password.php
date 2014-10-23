<?php
include dirname(__FILE__).'/dbconnection.php';
include dirname(__FILE__).'/getConfiguration.php';

// include our own variant with SHA512 support if required
// this is needed together with HAproxy basic authentican in case the base system
// does not support correct blowfish $2y$
if (!defined('PASSWORD_DEFAULT') && $__CONFIG['hash_algorithm'] == "PASSWORD_SHA512") {
	include dirname(__FILE__)."/password_compat/lib/password_sha512.php";

// fallback to PHP default in case we have PHP >=5.5
} elseif (defined('PASSWORD_DEFAULT') && $__CONFIG['hash_algorithm'] == "PASSWORD_SHA512") {
  $__CONFIG['hash_algorithm'] = "PASSWORD_DEFAULT";

// just include the default compatibility lib for PHP <5.5
} else {
	include dirname(__FILE__)."/password_compat/lib/password.php";
}
