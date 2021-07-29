<?php
/**
 * Standard PerfOpsOne resources handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace PerfOpsOne;

/**
 * Standard PerfOpsOne resources handling.
 *
 * This class defines all code necessary to initialize and handle PerfOpsOne admin menus.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

if ( ! class_exists( 'PerfOpsOne\Resources' ) ) {
	class Resources {

		/**
		 * The PerfOpsOne admin menus.
		 *
		 * @since  2.0.0
		 * @var    array $menus Maintains the PerfOpsOne admin menus.
		 */
		private static $menus = [];

		/**
		 * Returns a base64 svg resource for the PerfOpsOne logo.
		 *
		 * @return string The svg resource as a base64.
		 * @since 2.0.0
		 */
		public static function get_base64_logo() {
			$source  = '<svg width="100%" height="100%" viewBox="0 0 1001 1001" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-miterlimit:10;">';
			$source .= '<g id="apcu-manager" serif:id="APCu Manager" transform="matrix(10.0067,0,0,10.0067,0,0)">';
			$source .= '<rect x="0" y="0" width="100" height="100" style="fill:none;"/>';
			$source .= '<g id="Icons" transform="matrix(0.416389,0,0,0.416389,28.481,2.3984)">';
			$source .= '<g transform="matrix(0,-119.484,-119.484,0,50.731,119.595)"><path d="M0.95,0.611C0.95,0.632 0.933,0.649 0.911,0.649L0.174,0.649C0.153,0.649 0.136,0.632 0.136,0.611L0.136,-0.611C0.136,-0.632 0.153,-0.649 0.174,-0.649L0.911,-0.649C0.933,-0.649 0.95,-0.632 0.95,-0.611L0.95,0.611Z" style="fill:url(#_Linear1);fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(2.31646,0,0,2.31646,-5.58445,47.5101)"><path d="M0,15.324L15.325,3.648L29.189,15.324L46.163,0" style="fill:none;fill-rule:nonzero;stroke:rgb(65,172,255);stroke-width:0.63px;"/></g>';
			$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,-6.02226,77.8983)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,31.0411,52.4172)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,61.1551,77.8983)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(0,-2.31646,-2.31646,0,100.535,43.1514)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(-2.20436e-17,-6.27646,-5.91646,-2.20436e-17,117.335,83.7114)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(-2.31646,0,0,2.31646,265.004,3.77157)"><rect x="65" y="34" width="12" height="4" style="fill:white;"/></g>';
			$source .= '<g transform="matrix(-2.31646,0,0,2.31646,70.421,-88.8869)"><rect x="23" y="54" width="12" height="4" style="fill:white;"/></g>';
			$source .= '<g transform="matrix(2.31646,0,0,2.31646,-63.9338,-61.0893)"><g opacity="0.3"><g transform="matrix(1,0,0,1,83,29)"><path d="M0,6L-67,6L-67,2C-67,0.896 -66.104,0 -65,0L-2,0C-0.896,0 0,0.896 0,2L0,6Z" style="fill:white;fill-rule:nonzero;"/></g></g></g>';
			$source .= '<g transform="matrix(2.31646,0,0,2.31646,-63.9338,-61.0893)"><g opacity="0.3"><g transform="matrix(1,0,0,1,0,6)"><rect x="20" y="33" width="59" height="28" style="fill:white;"/></g></g></g>';
			$source .= '<g transform="matrix(0,-88.9995,-93.1112,0,54.5887,204.54)"><path d="M0.629,0.034L0.629,0.633C0.629,0.68 0.366,0.964 0.366,0.964L0.129,0.682C0.124,0.67 0.129,0.586 0.129,0.571L0.24,0.016L0.24,-0.018L0.24,-0.649C0.185,-0.666 0.185,-0.695 0.185,-0.695L0.351,-0.964C0.351,-0.964 0.629,-0.699 0.629,-0.647L0.629,0.034Z" style="fill:url(#_Linear2);fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(0,-72.1772,-75.5118,0,53.0533,251.205)"><path d="M1.148,1.095C1.148,1.17 1.087,1.232 1.011,1.232L0.532,1.232C0.457,1.232 0.396,1.17 0.396,1.095L0.396,-1.095C0.396,-1.17 0.457,-1.232 0.532,-1.232L1.011,-1.232C1.087,-1.232 1.148,-1.17 1.148,-1.095L1.148,1.095Z" style="fill:url(#_Linear3);fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(0,18.7374,19.6031,0,-14.1133,189.472)"><circle cx="0.453" cy="0" r="0.264" style="fill:url(#_Linear4);"/></g>';
			$source .= '<g transform="matrix(5.16667,0,0,4.93851,-50.28,39.9276)"><g opacity="0.3"><g transform="matrix(1,0,0,-1,0,40)"><rect x="21" y="7" width="14" height="2" style="fill:white;"/></g></g></g>';
			$source .= '<g transform="matrix(0,-4.93851,-5.16667,0,11.72,193.021)"><path d="M-1,-1C-1.552,-1 -2,-0.552 -2,0C-2,0.552 -1.552,1 -1,1C-0.448,1 0,0.552 0,0C0,-0.552 -0.448,-1 -1,-1" style="fill:rgb(255,216,111);fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(1.06581e-16,-4.93851,-5.16667,0,37.5533,193.021)"><path d="M-1,-1C-1.552,-1 -2,-0.552 -2,0C-2,0.552 -1.552,1 -1,1C-0.448,1 0,0.552 0,0C0,-0.552 -0.448,-1 -1,-1" style="fill:rgb(255,216,111);fill-rule:nonzero;"/></g>';
			$source .= '<g transform="matrix(-9.20683,68.032,68.0292,9.19682,119.123,75.567)"><path d="M-0.179,-0.132L-0.085,-0.126C-0.082,-0.128 -0.079,-0.13 -0.076,-0.132C-0.065,-0.162 -0.051,-0.19 -0.035,-0.216C-0.035,-0.22 -0.035,-0.223 -0.035,-0.228L-0.098,-0.298C-0.11,-0.312 -0.109,-0.334 -0.095,-0.347L-0.044,-0.392L0.007,-0.437C0.021,-0.449 0.043,-0.448 0.056,-0.434L0.118,-0.363C0.123,-0.362 0.128,-0.361 0.134,-0.36C0.158,-0.371 0.185,-0.38 0.212,-0.385C0.216,-0.39 0.22,-0.395 0.224,-0.4L0.23,-0.494C0.231,-0.513 0.248,-0.527 0.267,-0.526L0.335,-0.522L0.402,-0.518C0.421,-0.517 0.436,-0.5 0.435,-0.481L0.429,-0.387C0.433,-0.38 0.438,-0.373 0.442,-0.366C0.463,-0.358 0.483,-0.348 0.502,-0.336C0.512,-0.337 0.52,-0.337 0.53,-0.338L0.601,-0.4C0.615,-0.413 0.637,-0.412 0.649,-0.397L0.739,-0.296C0.752,-0.281 0.751,-0.26 0.736,-0.247L0.661,-0.18C0.657,-0.177 0.654,-0.174 0.651,-0.17C0.665,-0.142 0.675,-0.112 0.681,-0.081C0.686,-0.08 0.691,-0.079 0.696,-0.079L0.805,-0.072C0.808,-0.072 0.812,-0.071 0.815,-0.069C0.818,-0.068 0.821,-0.066 0.823,-0.063C0.827,-0.058 0.83,-0.052 0.829,-0.044L0.825,0.032L0.82,0.108C0.819,0.115 0.816,0.122 0.811,0.126C0.809,0.128 0.806,0.13 0.803,0.131C0.799,0.132 0.796,0.133 0.792,0.133L0.684,0.126C0.683,0.126 0.682,0.126 0.682,0.126C0.677,0.126 0.673,0.126 0.668,0.126C0.658,0.157 0.645,0.185 0.628,0.211C0.63,0.215 0.633,0.219 0.636,0.223L0.703,0.298C0.715,0.312 0.714,0.334 0.7,0.347L0.649,0.392L0.598,0.437C0.584,0.449 0.562,0.448 0.549,0.434L0.487,0.363C0.478,0.361 0.469,0.36 0.46,0.358C0.439,0.367 0.418,0.375 0.397,0.38C0.391,0.387 0.386,0.393 0.381,0.4L0.375,0.494C0.374,0.513 0.357,0.527 0.338,0.526L0.271,0.522L0.203,0.518C0.184,0.517 0.169,0.5 0.171,0.481L0.176,0.387C0.173,0.382 0.169,0.377 0.165,0.371C0.139,0.362 0.114,0.351 0.091,0.336C0.086,0.337 0.081,0.337 0.075,0.338L0.005,0.4C-0.01,0.413 -0.032,0.412 -0.044,0.397L-0.089,0.346L-0.134,0.296C-0.147,0.281 -0.145,0.259 -0.131,0.247L-0.061,0.184C-0.06,0.18 -0.059,0.177 -0.058,0.173C-0.072,0.145 -0.082,0.116 -0.089,0.085C-0.092,0.083 -0.094,0.08 -0.097,0.078L-0.191,0.072C-0.21,0.071 -0.225,0.055 -0.223,0.036L-0.219,-0.032L-0.215,-0.1C-0.215,-0.101 -0.215,-0.102 -0.215,-0.102C-0.213,-0.12 -0.197,-0.133 -0.179,-0.132ZM0.443,0.009C0.448,-0.073 0.386,-0.143 0.305,-0.148C0.223,-0.153 0.153,-0.091 0.148,-0.01C0.143,0.072 0.205,0.142 0.286,0.147C0.364,0.152 0.432,0.096 0.442,0.019C0.443,0.016 0.443,0.012 0.443,0.009Z" style="fill:url(#_Linear5);fill-rule:nonzero;"/></g>';
			$source .= '</g>';
			$source .= '<g transform="matrix(-1.20714e-16,2.3878,0.416389,-1.20714e-16,53.6143,56.429)"><path d="M-5.5,-5.5L5.5,-5.5" style="fill:none;fill-rule:nonzero;stroke:url(#_Linear6);stroke-width:0.24px;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:0.49,0.49;"/></g></g>';
			$source .= '<defs>';
			$source .= '<linearGradient id="_Linear1" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,-3.75329e-05)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear4" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(-1,0,0,1,0.906002,4.44089e-16)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear5" x1="0" y1="0" x2="1" y2="-0.000139067" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,-2.77556e-17,-2.77556e-17,-1,0,-5.95027e-05)"><stop offset="0" style="stop-color:rgb(209,231,253);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
			$source .= '<linearGradient id="_Linear6" x1="0" y1="0" x2="1" y2="-0.940977" gradientUnits="userSpaceOnUse" gradientTransform="matrix(6.20711,6.20711,6.20711,-6.20711,-3.1036,-8.6036)"><stop offset="0" style="stop-color:rgb(255,216,111);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,147,8);stop-opacity:1"/></linearGradient>';
			$source .= '</defs>';
			$source .= '</svg>';

			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $source );
		}

		/**
		 * Returns a base64 svg resource for the PerfOpsOne menu item.
		 *
		 * @return string The svg resource as a base64.
		 * @since 2.0.0
		 */
		public static function get_menu_base64_logo() {
			$source  = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">';
			$source .= '<g id="perfopsone">';
			$source .= '<path fill="#9DA1A7" d="M92 57c0 2.2-1.8 4-4 4H69.2c-1.7 0-3.2-.9-3.8-2.6l-3-8.4-9.8 33.1c-.5 1.7-2.1 2.9-3.8 2.9h-.1c-1.8-.1-3.4-1.3-3.8-3.1L32 27l-7.8 31.1c-.4 1.8-2 2.9-3.9 2.9H4c-2.2 0-4-1.8-4-4s1.8-4 4-4h13.2L28.3 8.9c.4-1.8 2.1-2.9 3.9-2.9 1.8 0 3.4 1.3 3.9 3.1l13.2 57.2 9-30.4c.5-1.7 2-2.9 3.7-2.9 1.7-.1 3.3 1 3.9 2.6L72 53h16c2.2 0 4 1.8 4 4z"/>';
			$source .= '</g>';
			$source .= '</svg>';

			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $source );
		}
	}
}

