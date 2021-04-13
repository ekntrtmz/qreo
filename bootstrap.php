<?php

// include core classes for better performance
if (!class_exists('Lime\\App')) {
  include(__DIR__.'/lib/Lime/App.php');
  include(__DIR__.'/lib/LimeExtra/App.php');
  include(__DIR__.'/lib/LimeExtra/Controller.php');
}

function qreo($module = null) {
  static $app;
}