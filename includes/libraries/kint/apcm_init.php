<?php

use Kint\Kint;

if ( ! \defined('KINT_DIR')) {
	\define('KINT_DIR', __DIR__);
	\define('KINT_WIN', DIRECTORY_SEPARATOR !== '/');
	\define('KINT_PHP70', (\version_compare(PHP_VERSION, '7.0') >= 0));
	\define('KINT_PHP71', (\version_compare(PHP_VERSION, '7.1') >= 0));
	\define('KINT_PHP72', (\version_compare(PHP_VERSION, '7.2') >= 0));
	\define('KINT_PHP73', (\version_compare(PHP_VERSION, '7.3') >= 0));
	\define('KINT_PHP74', (\version_compare(PHP_VERSION, '7.4') >= 0));
	\define('KINT_PHP80', (\version_compare(PHP_VERSION, '8.0') >= 0));
	\define('KINT_PHP81', (\version_compare(PHP_VERSION, '8.1') >= 0));
}

// Dynamic default settings
Kint::$return                            = true;
Kint::$display_called_from               = false;
Kint::$expanded                          = true;
Kint::$plugins[]                         = 'Kint\\Parser\\BinaryPlugin';
Kint::$plugins[]                         = 'Kint\\Parser\\SerializePlugin';
\Kint\Parser\SerializePlugin::$safe_mode = false;

