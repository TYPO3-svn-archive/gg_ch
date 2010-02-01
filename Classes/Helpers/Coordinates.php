<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Definition of helper functions to work with WGS84 and CH1903
 * coordinate systems.
 * Functions found on
 * http://www.swisstopo.admin.ch/internet/swisstopo/fr/home/products/software/products/skripts.html
 *
 * @category    Helpers
 * @package     TYPO3
 * @subpackage  tx_ggch
 * @author      Xavier Perseguers <ypo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_ggch_Helpers_Coordinates {

	/**
	 * Converts WGS latitude / longitude (° dec) to CH x.
	 * 
	 * @param	float		$lat
	 * @param	float		$long
	 * @return	float
	 */
	public static function WGStoCHx($lat, $long) {
			// Converts degrees dec to sex
		$lat = self::DECtoSEX($lat);
		$long = self::DECtoSEX($long);

			// Converts degrees to seconds (sex)
		$lat = self::DEGtoSEC($lat);
		$long = self::DEGtoSEC($long);
  
			// Auxiliary values (% Bern)
		$lat_aux = ($lat - 169028.66) / 10000;
		$long_aux = ($long - 26782.5) / 10000;
  
			// Process X
		$x = 600072.37 
			+ 211455.93 * $long_aux 
			-  10938.51 * $long_aux * $lat_aux
			-      0.36 * $long_aux * pow($lat_aux, 2)
			-     44.54 * pow($long_aux, 3);

		return $x;
	}

	/**
	 * Converts WGS latitude / longitude (° dec) to CH y.
	 * @param	float		$lat
	 * @param	float		$long
	 * @return	float
	 */
	public static function WGStoCHy($lat, $long) {
			// Converts degrees dec to sex
		$lat = self::DECtoSEX($lat);
		$long = self::DECtoSEX($long);

  			// Converts degrees to seconds (sex)
		$lat = self::DEGtoSEC($lat);
		$long = self::DEGtoSEC($long);

			// Auxiliary values (% Bern)
		$lat_aux = ($lat - 169028.66) / 10000;
		$long_aux = ($long - 26782.5) / 10000;

			// Process Y
		$y = 200147.07
			+ 308807.95 * $lat_aux 
			+   3745.25 * pow($long_aux, 2)
			+     76.63 * pow($lat_aux, 2)
			-    194.56 * pow($long_aux, 2) * $lat_aux
			+    119.79 * pow($lat_aux, 3);

		return $y;
	}

	/**
	 * Converts CH y/x to WGS latitude.
	 * 
	 * @param	integer		$x
	 * @param	integer		$y
	 * @return	float
	 */
	public static function CHtoWGSlat($x, $y) {
			// Converts militar to civil and to unit = 1000km
			// Axiliary values (% Bern)
		$x_aux = ($x - 600000) / 1000000;
		$y_aux = ($y - 200000) / 1000000;
  
			// Process lat
		$lat = 16.9023892
			+  3.238272 * $y_aux
			-  0.270978 * pow($x_aux, 2)
			-  0.002528 * pow($y_aux, 2)
			-  0.0447   * pow($x_aux, 2) * $y_aux
			-  0.0140   * pow($y_aux, 3);
    
			// Unit 10000" to 1 " and converts seconds to degrees (dec)
		$lat = $lat * 100 / 36;

		return $lat;
	}

	/**
	 * Converts CH y/x to WGS longitude.
	 * 
	 * @param	integer		$x
	 * @param	integer		$y
	 * @return	float
	 */
	public static function CHtoWGSlong($x, $y) {
			// Converts militar to civil and to unit = 1000km
			// Auxiliary values (% Bern)
		$x_aux = ($x - 600000) / 1000000;
		$y_aux = ($y - 200000) / 1000000;
  
			// Process long
		$long = 2.6779094
			+ 4.728982 * $x_aux
			+ 0.791484 * $x_aux * $y_aux
			+ 0.1306   * $x_aux * pow($y_aux, 2)
			- 0.0436   * pow($x_aux, 3);

			// Unit 10000" to 1 " and converts seconds to degrees (dec)
		$long = $long * 100 / 36;

		return $long;
	}

	/**
	 * Converts SEX DMS angle to DEC.
	 * 
	 * @param	float		$angle
	 * @return	float
	 */
	private static function SEXtoDEC($angle) {
			// Extract DMS
  		$deg = intval($angle);
  		$min = intval(($angle - $deg) * 100);
		$sec = ((($angle - $deg) * 100) - $min) * 100;

			// Result in degrees sex (dd.mmss)
		return $deg + ($sec / 60 + $min) / 60;
	}

	/**
	 * Converts DEC angle to SEX DMS.
	 * @param	float		$angle
	 * @return	float
	 */
	private static function DECtoSEX($angle) {
			// Extract DMS
		$deg = intval($angle);
		$min = intval(($angle - $deg) * 60);
		$sec = ((($angle - $deg) * 60) - $min) * 60;   

			// Result in degrees sex (dd.mmss)
		return $deg + $min / 100 + $sec / 10000;
	}

	/**
	 * Converts ° angle to seconds.
	 * 
	 * @param	float		$angle
	 * @return	float
	 */
	private static function DEGtoSEC($angle) {
			// Extract DMS
		$deg = intval($angle);
		$min = intval(($angle - $deg) * 100);
		$sec = ((($angle - $deg) * 100) - $min) * 100;

			// Result in degrees sex (dd.mmss)
		return $sec + $min * 60 + $deg * 3600;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/gg_ch/Classes/Helpers/Coordinates.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/gg_ch/Classes/Helpers/Coordinates.php']);
}

?>