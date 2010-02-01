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
 * Hook for EXT:rggooglemap.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_ggch
 * @author      Xavier Perseguers <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class user_tx_rggooglemap_hook {

	/**
	 * Prepares a region dataset.
	 * 
	 * @param	array		$params
	 * @param	tx_rggooglemap_pi1		$pObj
	 */
	public function prepareDataset(array $params, tx_rggooglemap_pi1 $pObj) {
		$pObj->xmlStartLevel('regions');

		// -----------------------------------------------
		// REGION [start]

		$vertices = array();
		$vertices[] = array(46.773451, 7.184417);
		$vertices[] = array(46.78068, 7.181589);
		$vertices[] = array(46.77955, 7.160769);
		$vertices[] = array(46.787909, 7.163307);
		$vertices[] = array(46.785972, 7.152751);
		$vertices[] = array(46.78068, 7.145024);
		$vertices[] = array(46.773451, 7.142196);
		$vertices[] = array(46.766222, 7.145024);
		$vertices[] = array(46.76093, 7.152751);
		$vertices[] = array(46.758993, 7.163307);
		$vertices[] = array(46.76093, 7.173862);
		$vertices[] = array(46.766222, 7.181589);
		$vertices[] = array(46.773451, 7.184417);

		$serializedVertices = '';
		foreach ($vertices as $vertex) {
			if ($serializedVertices !== '') $serializedVertices .= ',';
			$serializedVertices .= $vertex[0] . ',' . $vertex[1];
		}

		$attributes = array(
			'vertices' => $serializedVertices,
			'strokeColor' => '#FF0000',
			'strokeWeight' => '2',
			'strokeOpacity' => '.5',
			'fillColor' => '#0000F0',
			'fillOpacity' => '.1',
		);

		$pObj->xmlStartLevel('region', $attributes, TRUE);

		// REGION [end]
		// -----------------------------------------------

		$pObj->xmlEndLevel('regions');
	}

	/**
	 * Returns JavaScript code to be used to process the prepared dataset.
	 * 
	 * @param	tx_rggooglemap_pi1		$pObj
	 */
	public function getDatasetJSProcessing(tx_rggooglemap_pi1 $pObj) {
		return <<<EOT
			var regions = xmlDoc.documentElement.getElementsByTagName('region');
			for (var i = 0; i < regions.length; i++) {
				// Obtain the list of vertices
				var vertices = regions[i].getAttribute('vertices').split(',');
				var coordinates = [];
				for (var j = 0; j < vertices.length; j += 2) {
					coordinates.push(new GLatLng(parseFloat(vertices[j]), parseFloat(vertices[j + 1])));
				}
				var strokeColor = regions[i].getAttribute('strokeColor');
				var strokeWeight = regions[i].getAttribute('strokeWeight');
				var strokeOpacity = regions[i].getAttribute('strokeOpacity');
				var fillColor = regions[i].getAttribute('fillColor');
				var fillOpacity = regions[i].getAttribute('fillOpacity');

				surface = new GPolygon(coordinates, strokeColor, parseFloat(strokeWeight), parseFloat(strokeOpacity), fillColor, parseFloat(fillOpacity));
				map.addOverlay(surface);
			}
EOT;
	}
}

?>