<?php
/**
 * This file is part of the Qreo project.
 *
 * (c) Ekin Tertemiz - http//ekn.dev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define('QREO_ADMIN', 1);


// set default timezone
date_default_timezone_set('UTC');

// handle php webserver
if (PHP_SAPI == 'cli-server' && is_file(__DIR__.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
  return false;
}

// bootstrap qreo
require(__DIR__.'/bootstrap.php');

# admin route
if (QREO_ADMIN && !defined('QREO_ADMIN_ROUTE')) {
  $route = preg_replace('#'.preg_quote(QREO_BASE_URL, '#').'#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1);
  define('QREO_ADMIN_ROUTE', $route == '' ? '/' : $route);
}

// run backend
$cockpit->set('route', QREO_ADMIN_ROUTE)->trigger('admin.init')->run();
