<?php

include_once ('OpenID/AX.php');
include_once ('OpenID/google_discovery.php');
include_once ('../opsworks.php');

function get_oid_store() {
   /**
    * This is where the example will store its OpenID information.
    * You should change this path if you want the example store to be
    * created elsewhere.  After you're done playing with the example
    * script, you'll have to remove this directory manually.
    */
    $store_path = null;
    if (function_exists('sys_get_temp_dir')) {
      $store_path = sys_get_temp_dir();
    } else {
      if (strpos(PHP_OS, 'WIN') === 0) {
        $store_path = $_ENV['TMP'];
        if (!isset($store_path)) {
          $dir = 'C:\Windows\Temp';
        }
      } else {
        $store_path = @$_ENV['TMPDIR'];
        if (!isset($store_path)) {
          $store_path = '/tmp';
        }
      }
     }

     $store_path .= DIRECTORY_SEPARATOR . 'phpmMyAdmin_oid';

    if (!file_exists($store_path) && !mkdir($store_path)) {
      print "Could not create the FileStore directory '$store_path'. ".
        " Please check the effective permissions.";

      exit(0);
    }

    return new Auth_OpenID_FileStore($store_path);
}

/* Need to have cookie visible from parent directory */
session_set_cookie_params(0, '/', '', 0);
/* Create signon session */
$session_name = 'SignonSession';
session_name($session_name);
session_start();

// Determine realm and return_to
$base = 'http';
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $base .= 's';
}
$base .= '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];

$realm = $base . '/';
$returnTo = $base . dirname($_SERVER['PHP_SELF']);
if ($returnTo[strlen($returnTo) - 1] != '/') {
    $returnTo .= '/';
}
$returnTo .= 'openid';

// Start the OpenID stuff
if (count($_GET) == 0) {
  $consumer = new Auth_OpenID_Consumer(get_oid_store());
  new GApps_OpenID_Discovery($consumer, array('/etc/ssl/certs'));
  $auth_request = $consumer->begin('midwestfleet.com');

  header('Location: ' + $auth_request->redirectURL('http://*.m2.midwestfleet.com', $returnTo));
} else { // Finish the process
  $consumer = new Auth_OpenID_Consumer(get_oid_store());
  new GApps_OpenID_Discovery($consumer, array('/etc/ssl/certs'));
  $response = $consumer->complete($returnTo);

  if ($response->status == Auth_OpenID_SUCCESS) {
    $cfg = new OpsWorksDb();
    $_SESSION['PMA_single_signon_user'] = $cfg->username;
    $_SESSION['PMA_single_signon_password'] = $cfg->password;
    session_write_close();

    header('Location: ' + $returnTo);
  } else {
    exit;
  }
}

?>
