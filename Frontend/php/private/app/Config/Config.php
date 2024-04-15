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

// Datenschutz & Impressum
Config::set('rights.impressum.loadFromRossamedia', false);
Config::set('rights.datenschutz.loadFromRossamedia', false);
Config::set('rights.firma', '{{FIRMA}}');
Config::set('rights.strasse', '{{STRASSE}}');
Config::set('rights.plz', '{{PLZ}}');
Config::set('rights.ort', '{{ORT}}');
Config::set('rights.land', 'Deutschland');
Config::set('rights.telefon', '{{TELEFON}}');
Config::set('rights.telefax', '{{TELEFAX}}');
Config::set('rights.email', '{{EMAIL}}');
Config::set('rights.vorname', '{{VORNAME}}');
Config::set('rights.nachname', '{{NACHNAME}}');
Config::set('rights.impressum.handelsregister', '');
Config::set('rights.impressum.registergericht', '');
Config::set('rights.impressum.umsatzsteuerid', '');
Config::set('rights.impressum.webdesign', true);
Config::set('rights.impressum.handmade', true);
Config::set('rights.datenschutz.hoster', 'strato');
Config::set('rights.datenschutz.ssl', true);
Config::set('rights.datenschutz.cookies', false);
Config::set('rights.datenschutz.kontaktformular', false);
Config::set('rights.datenschutz.google_analytics', false);
Config::set('rights.datenschutz.google_adsense', false);
Config::set('rights.datenschutz.newsletter', false);
Config::set('rights.datenschutz.youtube', false);
Config::set('rights.datenschutz.vimeo', false);
Config::set('rights.datenschutz.google_maps', false);
Config::set('rights.datenschutz.google_recaptcha', false);
Config::set('rights.datenschutz.bewerbungen', false);

// Mail
Config::set('mail.smtp.host', '');
Config::set('mail.smtp.user', '');
Config::set('mail.smtp.pass', '');
Config::set('mail.smtp.auth', true); // true, false
Config::set('mail.smtp.secure', 'tls'); // tls, ssl
Config::set('mail.smtp.port', 587); // 25=not secure, 587=tls, 465=ssl
Config::set('mail.fromAddress', '');
Config::set('mail.fromName', '');
Config::set('mail.url', '');
Config::set('mail.logo', '');
Config::set('mail.fontFamily', 'Roboto');
Config::set('mail.fontFamily.headlines', 'Roboto');
Config::set('mail.linkColor', '#0099aa');

// Google Analytics
Config::set('googleAnalytics', '');

// Adblock Checker
Config::set('adblockChecker', false);

// Session
Config::set('sessionEnabled', false);
Config::set('sessionName', 'uId');
Config::set('cookieName', 'uHash');
Config::set('cookieNameSecure', 'uHash_secure');
Config::set('forceCookieLogin', true);

Config::set('jwt.key', 'c1b70c6f99a691b520634e944a76168a6cf78c6041b07ce54a5737c22e1fd1b5595893eccb018b682f79b3e76a8a1a677d6ae7582af717490b1315cecb57d6c98425d9b42187f2a27b8282b5f98d2f8ab187814e858a5122b803fee13af2bbaeb365f6c5da5181b2f64622bbf86805c9819461c1e20265b3927374b4953f43a50b2fc3f46c3156ee3457c3756728958f6b7e047a8c2406a0f81c49ee51197ebc');