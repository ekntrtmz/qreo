<?php
/**
 * This file is part of the Qreo project.
 *
 * (c) Ekin Tertemiz - http//ekn.dev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 /**
 * QREO start time
 */
define('QREO_START_TIME', microtime(true));

// Autoload vendor libs
include(__DIR__.'/lib/vendor/autoload.php');

// include core classes for better performance
if (!class_exists('Lime\\App')) {
  include(__DIR__.'/lib/Lime/App.php');
  include(__DIR__.'/lib/LimeExtra/App.php');
  include(__DIR__.'/lib/LimeExtra/Controller.php');
}

/*
 * Autoload from lib folder (PSR-0)
 */

spl_autoload_register(function($class){
  $class_path = __DIR__.'/lib/'.str_replace('\\', '/', $class).'.php';
  if(file_exists($class_path)) include_once($class_path);
});

// load .env file if exists
DotEnv::load(__DIR__);

/*
 * Collect needed paths
 */

$QREO_DIR         = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__);

$QREO_BASE        = trim( $QREO_DIR, "/");
$QREO_BASE_URL    = strlen($QREO_BASE) ? "/{$QREO_BASE}": $QREO_BASE;
$QREO_BASE_ROUTE  = $QREO_BASE_URL;

/*
 * SYSTEM DEFINES
 */
if (!defined('QREO_DIR'))                    define('QREO_DIR'            , $QREO_DIR);
if (!defined('QREO_ADMIN'))                  define('QREO_ADMIN'          , 0);
//if (!defined('COCKPIT_DOCS_ROOT'))              define('COCKPIT_DOCS_ROOT'      , $COCKPIT_DOCS_ROOT);
if (!defined('QREO_ENV_ROOT'))               define('QREO_ENV_ROOT'       , QREO_DIR);
if (!defined('QREO_BASE_URL'))               define('QREO_BASE_URL'       , $QREO_BASE_URL);
//if (!defined('COCKPIT_API_REQUEST'))            define('COCKPIT_API_REQUEST'    , QREO_ADMIN && strpos($_SERVER['REQUEST_URI'], COCKPIT_BASE_URL.'/api/')!==false ? 1:0);
if (!defined('QREO_SITE_DIR'))               define('QREO_SITE_DIR'       , QREO_ENV_ROOT == QREO_DIR ?  ($COCKPIT_DIR == dirname(QREO_DIR) ) :  QREO_ENV_ROOT);
if (!defined('QREO_CONFIG_DIR'))             define('QREO_CONFIG_DIR'     , QREO_ENV_ROOT.'/config');
if (!defined('QREO_BASE_ROUTE'))             define('QREO_BASE_ROUTE'     , $QREO_BASE_ROUTE);
if (!defined('QREO_STORAGE_FOLDER'))         define('QREO_STORAGE_FOLDER' , QREO_ENV_ROOT.'/storage');
if (!defined('QREO_ADMIN_CP'))               define('QREO_ADMIN_CP'       , QREO_ADMIN);
if (!defined('QREO_PUBLIC_STORAGE_FOLDER'))  define('QREO_PUBLIC_STORAGE_FOLDER' , QREO_ENV_ROOT.'/storage');

if (!defined('QREO_CONFIG_PATH')) {
  $_configpath = QREO_CONFIG_DIR.'/config.'.(file_exists(QREO_CONFIG_DIR.'/config.php') ? 'php':'yaml');
  define('QREO_CONFIG_PATH', $_configpath);
}

function qreo() {
  
  static $app;

  if(!$app) {

    $customConfig = [];

    // load custom config
    if (file_exists(QREO_CONFIG_PATH)) {
      $customConfig = preg_match('/\.yaml$/', QREO_CONFIG_PATH) ? Spyc::YAMLLoad(QREO_CONFIG_PATH) : include(QREO_CONFIG_PATH);
    }

    // load config
    $config = array_replace_recursive([

        'debug'        => preg_match('/(localhost|::1|\.local)$/', @$_SERVER['SERVER_NAME']),
        'app.name'     => 'Cockpit',
        'base_url'     => QREO_BASE_URL,
        'base_route'   => QREO_BASE_ROUTE,
        //'docs_root'    => COCKPIT_DOCS_ROOT,
        'session.name' => md5(QREO_ENV_ROOT),
        'session.init' => QREO_ADMIN,
        'sec-key'      => 'c3b40c4c-db44-s5h7-a814-b4931a15e5e1', //  ???
        'i18n'         => 'en',
        //'database'     => ['server' => 'mongolite://'.(COCKPIT_STORAGE_FOLDER.'/data'), 'options' => ['db' => 'cockpitdb'], 'driverOptions' => [] ],
        //'memory'       => ['server' => 'redislite://'.(COCKPIT_STORAGE_FOLDER.'/data/cockpit.memory.sqlite'), 'options' => [] ],

        'paths'         => [
            '#root'     => QREO_DIR,
            '#storage'  => QREO_STORAGE_FOLDER,
            '#pstorage' => QREO_PUBLIC_STORAGE_FOLDER,
            '#data'     => QREO_STORAGE_FOLDER.'/data',
            '#cache'    => QREO_STORAGE_FOLDER.'/cache',
            '#tmp'      => QREO_STORAGE_FOLDER.'/tmp',
            //'#thumbs'   => QREO_PUBLIC_STORAGE_FOLDER.'/thumbs',
            //'#uploads'  => QREO_PUBLIC_STORAGE_FOLDER.'/uploads',
            //'#modules'  => QREO_DIR.'/modules',
            //'#addons'   => QREO_ENV_ROOT.'/addons',
            '#config'   => QREO_CONFIG_DIR,
            'assets'    => QREO_DIR.'/assets',
            'site'      => QREO_SITE_DIR
        ],

    ], is_array($customConfig) ? $customConfig : []);    

    $app = new LimeExtra\App($config);

    $app['config'] = $config;

    // register paths
    foreach ($config['paths'] as $key => $path) {
      $app->path($key, $path);
    }

    // key-value storage
    $app->service('memory', function() use($config) {
        $client = new SimpleStorage\Client($config['memory']['server'], $config['memory']['options']);
        return $client;
    });

    // mailer service
    $app->service('mailer', function() use($app, $config){
          
        $options = isset($config['mailer']) ? $config['mailer']:[];

        if (is_string($options)) {
            parse_str($options, $options);
        }

        $mailer    = new \Mailer($options['transport'] ?? 'mail', $options);
        return $mailer;
    });    

    // set cache path
    $tmppath = $app->path('#tmp:');

    $app('cache')->setCachePath($tmppath);
    $app->renderer->setCachePath($tmppath);

    // i18n
    //$app('i18n')->locale = $config['i18n'] ?? 'en';

    // handle exceptions
    if (QREO_ADMIN) {

      set_exception_handler(function($exception) use($app) {

          $error = [
              'message' => $exception->getMessage(),
              'file' => $exception->getFile(),
              'line' => $exception->getLine(),
          ];

          if ($app['debug']) {
              //$body = $app->request->is('ajax') ? json_encode(['error' => $error['message'], 'file' => $error['file'], 'line' => $error['line']]) : $app->render('cockpit:views/errors/500-debug.php', ['error' => $error]);
          } else {
              //$body = $app->request->is('ajax') ? '{"error": "500", "message": "system error"}' : $app->view('cockpit:views/errors/500.php');
          }

          $app->trigger('error', [$error, $exception]);

          header('HTTP/1.0 500 Internal Server Error');
          echo $body;

          if (function_exists('cockpit_error_handler')) {
              cockpit_error_handler($error);
          }
      });
    }

    // load config global bootstrap file
    if ($custombootfile = $app->path('#config:bootstrap.php')) {
      include($custombootfile);
    }

    $app->trigger('cockpit.bootstrap');

  }

  return $app;

}

$qreo = qreo();