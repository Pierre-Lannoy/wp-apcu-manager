<?php

use APCMKint\Kint;

if ( ! \defined('APCMKINT_DIR')) {
	\define('APCMKINT_DIR', __DIR__);
	\define('APCMKINT_WIN', DIRECTORY_SEPARATOR !== '/');
	\define('APCMKINT_PHP70', (\version_compare(PHP_VERSION, '7.0') >= 0));
	\define('APCMKINT_PHP71', (\version_compare(PHP_VERSION, '7.1') >= 0));
	\define('APCMKINT_PHP72', (\version_compare(PHP_VERSION, '7.2') >= 0));
	\define('APCMKINT_PHP73', (\version_compare(PHP_VERSION, '7.3') >= 0));
	\define('APCMKINT_PHP74', (\version_compare(PHP_VERSION, '7.4') >= 0));
	\define('APCMKINT_PHP80', (\version_compare(PHP_VERSION, '8.0') >= 0));
	\define('APCMKINT_PHP81', (\version_compare(PHP_VERSION, '8.1') >= 0));
}

// Dynamic default settings
Kint::$return                            = true;
Kint::$display_called_from               = false;
Kint::$expanded                          = true;
Kint::$plugins[]                         = 'APCMKint\\Parser\\BinaryPlugin';
Kint::$plugins[]                         = 'APCMKint\\Parser\\SerializePlugin';
\APCMKint\Parser\SerializePlugin::$safe_mode = false;

