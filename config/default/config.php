<?php

return array(
  // Database connection
  'dbUser' => 'root',
  'dbPass' => '',
  'dbHost' => 'localhost',
  'dbName' => 'cider',
  'dbType' => 'mysql',
  'databases' => array(
    'default' => array(
      'user' => 'root',
      'password' => '',
      'host' => 'localhost',
      'driver' => 'mysql'
    )
  ),
  
  // Language
  'defaultLang' => 'en',
  'languages' => array('en', 'it'),

  // Theme
  'theme' => 'ciderbit2',
  'themes' => array('ciderbit2'),
  
  // Base directory
  'baseDir' => '/',
    
  'siteTitle' => 'CIDERbit',
  'siteSubtitle' => 'OO MVC Framework',
  
  'defaultPateTitle' => 'CIDERbit',
    
  'coreCache' => false,
  'debug' => false,
    
  'cacheDir' => dirname(__FILE__) . '/cache/',
  'filesDir' => dirname(__FILE__) . '/files/'
);