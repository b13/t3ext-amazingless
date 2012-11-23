<?php
/***************************************************************
*  Copyright notice
*
*  © 2012 Benjamin Mack (benni@typo3.org)
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
 * base class that hooks into the page renderer and calls LESS.PHP 
 * on all .less files
 */
class Tx_AmazingLess_PageRenderer {


	/**
	 * hook that is called before the page renderer does concatenation
	 * and minification
	 *
	 *	$params = array(
	 *	    'jsLibs' => &$this->jsLibs,
	 *	    'jsFooterLibs'   => &$this->jsFooterLibs,
	 *	    'jsFiles' => &$this->jsFiles,
	 *	    'jsFooterFiles' => &$this->jsFooterFiles,
	 *	    'cssFiles' => &$this->cssFiles,
	 *	    'headerData' => &$this->headerData,
	 *	    'footerData' => &$this->footerData,
	 *	    'jsInline' => &$this->jsInline,
	 *	    'jsFooterInline' => &$this->jsFooterInline,
	 *	    'cssInline' => &$this->cssInline,
	 *	);
	 *
	 * @param array $params
	 * @param t3lib_pageRenderer $pageRendererObject
	 * @return void
	 */
	public function preProcessHook(&$params, &$pageRendererObject) {

			// a new array is created so that the order of inclusion 
			// of the CSS files is preserved
		$originalCssFiles = $params['cssFiles'];
		if (is_array($originalCssFiles) && count($originalCssFiles)) {
			$modifiedCssFiles = array();
			foreach ($originalCssFiles as $fileName => $fileDetails) {
					// check if there is a .less file
				if ($this->isLessFile($fileName) === TRUE) {
					try {
						// make a CSS file out of the .less file
						$newFileName = $this->compileLessFile($fileName, $fileDetails);
						if ($newFileName !== FALSE) {
							$modifiedCssFiles[$newFileName] = $fileDetails;
							$modifiedCssFiles[$newFileName]['file'] = str_replace($fileName, $newFileName, $modifiedCssFiles[$newFileName]['file']);
						} else {
								// just keep it the old way
							$modifiedCssFiles[$fileName] = $fileDetails;
						}
					} catch (Exception $e) {
						var_dump($e->getMessage());
						exit;
						// TODO: log this exception
					}
				} else {
					$modifiedCssFiles[$fileName] = $fileDetails;
				}
			}
			
			$params['cssFiles'] = $modifiedCssFiles;
		}
	}


	/**
	 * simple helper function to check if the current file is a less file
	 * that has a .less file ending
	 *
	 * maybe this could go faster with a stripos() but this one is safer for now
	 *
	 * @param string $fileName the filename to check
	 * @return boolean whether this is a LESS file
	 */
	protected function isLessFile($fileName) {
		if (strcasecmp(substr($fileName, -5), '.less') === 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * processes the LESS file with lessPHP and creates a CSS file out of it
	 *
	 * @param string $lessFileName
	 * @param array $fileDetails the details on the file
	 * @param string $targetFileName the filename
	 * @return the filename
	 */
	public function compileLessFile($lessFileName, array $fileDetails, $targetFileName = NULL) {

		$this->includeLessCompilerClass();
	
			// check if this is a full filename
		if (t3lib_div::isAbsPath($lessFileName)) {
			$lessFileName = PATH_site . $lessFileName;
		}
		
			// create the target file name if necessary
			// which is based on the full file path / filename
			// LESS PHP compiler then checks if the file is newer than the LESS file
		if ($targetFileName === NULL) {

			$targetFileNamePrefix = strtolower(basename($lessFileName));
		
			// in frontend, we assume the file is somewhere like this
			// fileadmin/templates/less/main.less
			// we create this then to preserve paths: fileadmin/templates/css/main.css
			// but only if the directory exists
			$targetFileName = dirname(dirname($lessFileName)) . '/css/';
			if (TYPO3_MODE == 'FE' && is_dir($targetFileName)) {
				$targetFileName .= str_replace('.less', '.css', $targetFileNamePrefix);
				$targetFileName = PATH_site . $targetFileName;
			} else {

					// in the backend, we put all files in typo3temp/*.css
					// and hope that all paths have been written with absolute paths in mind
				$targetFileNamePrefix = str_replace('.less', '', $targetFileNamePrefix);
				$targetFileNamePrefix = str_replace('.', '_', $targetFileNamePrefix);
					// sha1 on the file NAME
				$targetFileName = t3lib_div::tempnam('amazingless_' . $targetFileNamePrefix . '_' . sha1($lessFileName)) . '.css';
			}
		
		
			// absolute file name of the target file
		} elseif (t3lib_div::isAbsPath($targetFileName) === FALSE) {
			$targetFileName = PATH_site . $targetFileName;
		}

			// create the less file (and let possible excpetions bubble up)
		$this->callLessCompiler($lessFileName, $targetFileName);

		if (is_file($targetFileName)) {
				// remove the prefix again
			return substr($targetFileName, strlen(PATH_site)-1);
		} else {
			return FALSE;
		}
	}

	/**
	 * function to include the source code of LESSPHP
	 *
	 * @return void
	 */
	protected function includeLessCompilerClass() {
		require_once(t3lib_extMgm::extPath('amazingless', 'Resources/Contrib/lessphp/lessc.inc.php'));
	}
	
	/**
	 * calls the LESS compiler
	 */
	protected function callLessCompiler($lessFileName, $targetFileName) {
			// create the less file (and let possible excpetions bubble up)

		if (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
			// create a new cache object, and compile
			$cache = lessc::cexecute($lessFileName);
			t3lib_div::writeFile($targetFileName, $cache['compiled']);
	
			// the next time we run, write only if it has updated
			$last_updated = $cache['updated'];
			$cache = lessc::cexecute($cache);
			if ($cache['updated'] > $last_updated) {
				t3lib_div::writeFile($targetFileName, $cache['compiled']);
			}

		} else {
			// basic caching
			lessc::ccompile($lessFileName, $targetFileName);
			t3lib_div::fixPermissions($targetFileName);
		}

	}
}