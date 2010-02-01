<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

	// Register hook for EXT:googlemap
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rggooglemap']['datasetHook'][] = 'EXT:gg_ch/Classes/Hooks/class.user_tx_rggooglemap_hook.php:user_tx_rggooglemap_hook';

?>