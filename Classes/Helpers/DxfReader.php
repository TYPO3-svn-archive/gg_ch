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
 * DXF (AutoCAD) ASCII file reader.
 *
 * @category    Helpers
 * @package     TYPO3
 * @subpackage  tx_ggch
 * @author      Xavier Perseguers <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class Tx_Gg_Ch_DxfReader {

	protected $filename;
	protected $handle;
	protected $buffer;
	protected $data = array();

	/**
	 * Creates a new instance of the Tx_Gg_Ch_DxfReader.
	 * 
	 * @param	string		$filename
	 */
	public function __construct($filename) {
		$this->filename = $filename;
	}

	/**
	 * Parses the DXF file.
	 * 
	 * @return	array
	 */
	public function parse() {
		$this->handle = fopen($this->filename, 'r');

		$this->parseHEADER();
		$this->parseTABLES();
		$this->parseBLOCKS();
		$this->parseENTITIES();

		fclose($this->handle);

		return $this->data;
	}

	/**
	 * Parses the HEADER section.
	 * 
	 * @return	void
	 */
	protected function parseHEADER() {
		$this->consume('0')->consume('SECTION');
		$this->consume('2')->consume('HEADER');

		$this->data['HEADER'] = array();
		while ($this->readLine() !== '0') ;

		$this->consume('ENDSEC');
	}

	/**
	 * Parses the TABLES section.
	 * 
	 * The TABLES section contains several tables, each of which in turn
	 * contains a variable number of table entries.
	 * 
	 * @return	void
	 */
	protected function parseTABLES() {
		$this->consume('0')->consume('SECTION');
		$this->consume('2')->consume('TABLES');

		$this->data['TABLES'] = array();
		while ($table = $this->parseTABLE()) {
			$this->data['TABLES'][] = $table;
		}

		$this->consume('ENDSEC');
	}

	/**
	 * Parses the BLOCKS section.
	 * 
	 * The BLOCKS section of the DXF file contains all the Block Definitions.
	 * This section contains the entities that make up the Blocks used in the
	 * drawing. The format of the entities in this section is identical to
	 * those in the ENTITIES section.
	 * 
	 * @return	void
	 */
	protected function parseBLOCKS() {
		$this->consume('0')->consume('SECTION');
		$this->consume('2')->consume('BLOCKS');

		$this->data['BLOCKS'] = array();
		while ($this->readLine() !== '0') ;

		$this->consume('ENDSEC');
	}

	/**
	 * Parses the ENTITIES section.
	 * 
	 * Entity items appear both in the BLOCK and ENTITIES sections of the
	 * DXF file.
	 * 
	 * @return	void
	 */
	protected function parseENTITIES() {
		$this->consume('0')->consume('SECTION');
		$this->consume('2')->consume('ENTITIES');

		$this->data['ENTITIES'] = array();
		while ($entity = $this->parseENTITY()) {
			$this->data['ENTITIES'][] = $entity;
		}

		$this->consume('ENDSEC');
	}

	/**
	 * Parses a TABLE section.
	 * 
	 * Each table is introduced with a 0 group with the label "TABLE".
	 * This is followed by a 2 group identifying the particular table
	 * (VPORT, LTYPE, LAYER, STYLE, VIEW, UCS, or DWGMGR) and a 70 group
	 * that specifies the maximum number of table entries that may follow.
	 * 
	 * @return	array
	 */
	protected function parseTABLE() {
		$table = array();

		$this->consume('0');
		$token = $this->readLine();
		if ($token === 'ENDSEC') {
			$this->unconsume($token);
			return null;
		} elseif ($token !== 'TABLE') {
			die("parseTABLE() failed. Expected: TABLE, found: $token\n");
		}

		$this->consume('2');
		$table['name'] = $this->readLine();
		$this->consume('70');
		$table['maxItems'] = intval($this->readLine());

		$table['entries'] = array();
		for ($i = 0; $i < $table['maxItems']; $i++) {
			$item = $this->parseTABLEitem($table['name']);
			if (!$item) {
				break;
			}
			$table['entries'][] = $item;
		}

		$this->consume('0')->consume('ENDTAB');
		return $table;
	}

	/**
	 * Parses a TABLE item.
	 * 
	 * Each table item consists of a 0 group identifying the item type
	 * (same as table name, e.g., "LTYPE" or "LAYER"), a 2 group giving
	 * the name of the table entry, a 70 group specifying flags relevant
	 * to the table entry, and additional groups that give the value of
	 * the table entry.
	 * 
	 * @param	string		$tableName
	 * @return	array
	 */
	protected function parseTABLEitem($tableName) {
		$item = array();

		$this->consume('0');
		$token = $this->readLine();
		if ($token === 'ENDTAB') {
			$this->unconsume($token)->unconsume('0');
			return null;
		} elseif ($token !== $tableName) {
			die("parseTABLEitem() failed. Expected: $tableName, found: $token\n");
		}

		$this->consume('2');
		$item['name'] = $this->readLine();
		$this->consume('70');
		$item['flags'] = intval($this->readLine());

		$item['value'] = array();
		while (($key = $this->readLine()) !== '0') {
			$item['value'][$key] = $this->readLine();
		}
		$this->unconsume('0');

		return $item;
	}

	/**
	 * Parses an ENTITY item.
	 * 
	 * Each entity begins with a 0 group identifying the entity type. Every
	 * entity conatins an 8 group that gives the name of the layer on which
	 * the entity resides. Each entity may have elevation, thickness,
	 * linetype, or color information associated with it.
	 * 
	 * @return	array
	 */
	protected function parseENTITY() {
		$entity = array();

		$this->consume('0');
		$token = $this->readLine();

		if ($token === 'ENDSEC') {
			$this->unconsume($token);
			return null;
		}

		$entity['type'] = $token;
		$this->consume('8');
		$entity['layer'] = $this->readLine();

		switch ($entity['type']) {
			case 'POLYLINE':
					// 66 = "vertices follow flag"
				$this->consume('66')->consume('1');
				$this->consume('70');
				$entity['flags'] = intval($this->readLine());

				$entity['value'] = array();
				while (($key = $this->readLine()) !== '0') {
					$entity['value'][$key] = $this->readLine();
				}

				$item['vertices'] = array();
				while ($vertex = $this->parseVERTEX($entity['layer'])) {
					$entity['vertices'][] = $vertex;
				}
				break;

			case 'POINT':
				$this->consume('10');
				$item['x'] = floatVal($this->readLine());
				$this->consume('20');
				$item['y'] = floatVal($this->readLine());
				$this->consume('30');
				$item['z'] = floatVal($this->readLine());

				$entity['value'] = array();
				while (($key = $this->readLine()) !== '0') {
					$entity['value'][$key] = $this->readLine();
				}
				$this->unconsume('0');
				break;

			default:
				die('Unknown entity type: ' . $entity['type']);
		}

		return $entity;
	}

	/**
	 * Parses a VERTEX section.
	 * 
	 * @param	string		$layer
	 * @return	array
	 */
	protected function parseVERTEX($layer) {
		$vertex = array();

		$token = $this->readLine();
		if ($token === 'SEQEND') {
			return null;
		} elseif ($token !== 'VERTEX') {
			die("parseVERTEX() failed. Expected: VERTEX, found: $token\n");
		}

		$this->consume('8')->consume($layer);
		$this->consume('10');
		$vertex['x'] = floatval($this->readLine());
		$this->consume('20');
		$vertex['y'] = floatval($this->readLine());
		$this->consume('30');
		$vertex['z'] = floatval($this->readLine());

		$vertex['value'] = array();
		while (($key = $this->readLine()) !== '0') {
			$vertex['value'][$key] = $this->readLine();
		}

		return $vertex;
	}

	/**
	 * Consumes a token from the input stream.
	 * 
	 * @return	DxfReader		This instance to allow method chaining
	 */
	protected function consume($str) {
		$line = $this->readLine();
		if ($line !== $str) {
			die("consume() failed. Expected: $str, found: $line\n");
		}
		return $this;
	}

	/**
	 * "Unconsumes" a token from the input stream.
	 * 
	 * @return	DxfReader		this instance to allow method chaining
	 */
	protected function unconsume($str) {
		$this->buffer = $str . "\n" . $this->buffer;
		return $this;
	}

	/**
	 * Reads a line from current file.
	 * 
	 * @return	string
	 */
	protected function readLine() {
		if (!$this->buffer || strpos($this->buffer, "\n") === FALSE) {
			$buffer = fread($this->handle, 4096);
			$this->buffer .= $buffer;
		}
		$pos = strpos($this->buffer, "\n");
		if ($pos > 0) {
			$line = rtrim(substr($this->buffer, 0, $pos), "\r");
			$this->buffer = substr($this->buffer, $pos + 1);
		} else {
			$line = rtrim($this->buffer, "\r");
			$this->buffer = '';
		}
		return trim($line);
	}

}

$reader = new Tx_Gg_Ch_DxfReader('../../Resources/Private/demo.dxf');
$data = $reader->parse();

print_r($data);
?>