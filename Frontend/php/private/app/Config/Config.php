<?php
namespace Webapp\Config;

use Webapp\Core\Config;

// set date/time
date_default_timezone_set('Europe/Berlin');

// Disable phar execution
if(in_array('phar', stream_get_wrappers())) stream_wrapper_unregister('phar');

// Dev mode
Config::set('dev', true);

// Updater
Config::set('updater', false);

// Darkmode
Config::set('darkmodeCheck', true);

// Gobal Site
Config::set('languages', ['de']);
Config::set('languageCodes', [
	'de' => 'de_DE',
	'en' => 'en_US',
]);
Config::set('forceHttps', false);

// Router
Config::set('defaultLanguage', 'de');
Config::set('defaultController', 'pages');
Config::set('defaultControllerSuffix', 'Controller');
Config::set('defaultMethod', 'startseite');
Config::set('defaultFilename', 'startseite');
Config::set('defaultLayout', 'frontend');
Config::set('defaultErrorLayout', 'frontend');

// DB
Config::set('db.active', false);
Config::set('db.host', '127.0.0.1');
Config::set('db.user', '');
Config::set('db.password', '');
Config::set('db.name', '');

Config::set('jwt.key', 'c1b70c6f99a691b520634e944a76168a6cf78c6041b07ce54a5737c22e1fd1b5595893eccb018b682f79b3e76a8a1a677d6ae7582af717490b1315cecb57d6c98425d9b42187f2a27b8282b5f98d2f8ab187814e858a5122b803fee13af2bbaeb365f6c5da5181b2f64622bbf86805c9819461c1e20265b3927374b4953f43a50b2fc3f46c3156ee3457c3756728958f6b7e047a8c2406a0f81c49ee51197ebc');
Config::set('backend.url', "http://localhost:8080/rest");