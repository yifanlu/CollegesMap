<?php

/**
 * This class provides static methods that return pieces of data specific to
 * your app
 */
class AppInfo {
  public static function appID() {
    return "Facebook app id here.";
  }

  public static function appSecret() {
    return "Facebook app secret here.";
  }
  
  public static function mapsKey(){
    return "Google Maps API key here.";
  }

  public static function getUrl($path = '/') {
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
      || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
    ) {
      $protocol = 'https://';
    }
    else {
      $protocol = 'http://';
    }

    return $protocol . $_SERVER['HTTP_HOST'] . $path;
  }
  
  public static function getPageUrl($path = '/') {
  	return "https://apps.facebook.com/fburlhere" . $path;
  }
  
  public static function getDatabaseDSN(){
	return "pgsql:dbname=namehere;host=hosthere;user=username;password=password";
  }
}
