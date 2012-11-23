<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

	// add a hook to the page renderer before files are concatenated
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['tx_amazingless'] = 'Tx_AmazingLess_PageRenderer->preProcessHook';
