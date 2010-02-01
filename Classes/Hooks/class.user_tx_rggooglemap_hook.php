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
	 * @return	void
	 */
	public function prepareDataset(array $params, tx_rggooglemap_pi1 $pObj) {
		$pObj->xmlStartLevel('regions');

		// -----------------------------------------------
		// REGION [start]

		$filename = t3lib_extMgm::extPath('gg_ch') . 'Resources/Private/gg25.dxf';
		$dxfReader = t3lib_div::makeInstance('tx_ggch_Helpers_DxfReader', $filename);
		$data = $dxfReader->parse();

		$gemeinde = $data['ENTITIES'][33];
		$vertices = $this->polylineToGgmapOverlay($gemeinde);

		$attributes = array(
			'vertices' => $this->serializeVertices($vertices),
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
	 * @return	string		JavaScript code
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

	/**
	 * Converts CH1903 coordinates (from GG25 data) in a POLYLINE to
	 * latitude/longitude to be used by GoogleMap.
	 * 
	 * @param	array		$polyline
	 * @return	array
	 */
	protected function polylineToGgmapOverlay(array $polyline) {	
		if ($polyline['type'] !== 'POLYLINE') {
			throw new Exception('Not a GG polyline.', 1265008560);
		}
		$ggmapVertices = array();
		foreach ($polyline['vertices'] as $vertex) {
			$x = $vertex['x'];
			$y = $vertex['y'];
			$lat = tx_ggch_Helpers_Coordinates::CHtoWGSlat($x, $y);
			$lng = tx_ggch_Helpers_Coordinates::CHtoWGSlong($x, $y);
			$ggmapVertices[] = array($lat, $lng);
		}
		return $ggmapVertices;
	}

	/**
	 * Serializes an array of vertices.
	 * 
	 * @param	array		$vertices
	 * @return	string
	 */
	protected function serializeVertices(array $vertices) {
		$content = '';
		foreach ($vertices as $vertex) {
			if ($content !== '') {
				$content .= ',';
			}
			$content .= $vertex[0] . ',' . $vertex[1];
		}
		return $content;
	}

}

?>