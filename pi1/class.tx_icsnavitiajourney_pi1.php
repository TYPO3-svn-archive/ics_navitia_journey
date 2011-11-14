<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 In Cité Solution <technique@in-cite.net>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin 'Journey' for the 'ics_navitia_journey' extension.
 *
 * @author	In Cité Solution <technique@in-cite.net>
 * @package	TYPO3
 * @subpackage	tx_icsnavitiajourney
 */
class tx_icsnavitiajourney_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_icsnavitiajourney_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_icsnavitiajourney_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ics_navitia_journey';	// The extension key.
	
	private $login;
	private $url;
	private $dataProvider;
	var $pictoLine;
	var $templates;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		$this->init();
		
		if (!$entryPointStart && (!empty($this->piVars['startName']) || !empty($this->piVars['startCity'])))
			$entryPointStart = $this->dataProvider->getEntryPointListByNameAndCity($this->piVars['startName'], $this->piVars['startCity']);
		if (!$entryPointArrival && (!empty($this->piVars['arrivalName']) || !empty($this->piVars['arrivalCity'])))
			$entryPointArrival = $this->dataProvider->getEntryPointListByNameAndCity($this->piVars['arrivalName'], $this->piVars['arrivalCity']);

		if (is_numeric($this->piVars['entryPointStart']) && is_numeric($this->piVars['entryPointArrival']) && $entryPointStart->Count() && $entryPointArrival->Count()) {
			$entryPointFinalStart = $entryPointStart->Get(intval($this->piVars['entryPointStart']));
			$entryPointFinalArrival = $entryPointArrival->Get(intval($this->piVars['entryPointArrival']));
			
			$entryPointDefinitionStart = tx_icslibnavitia_EntryPointDefinition::fromEntryPoint($entryPointFinalStart);
			$entryPointDefinitionArrival = tx_icslibnavitia_EntryPointDefinition::fromEntryPoint($entryPointFinalArrival);
			
			$params = array(
				'startName' => $entryPointFinalStart->name,
				'startCity' => $entryPointFinalStart->cityName,
				'arrivalName' => $entryPointFinalArrival->name,
				'arrivalCity' => $entryPointFinalArrival->cityName,
			);
			
			$aDate = explode('/', $this->piVars['date']);
			$aTime = explode(':', $this->piVars['hour']);
			
			$date = new DateTime;
			$date->setDate($aDate[2], $aDate[1], $aDate[0]);
			$date->setTime($aTime[0], $aTime[1]);
			
			if ($this->piVars['isStartTime']) {
				$isStartTime = true;
			}
			else {
				$isStartTime = false;
			}
			
			$binaryCriteria = $this->dataProvider->getBinaryCriteria($this->piVars['mode']);
			
			$planJourney = $this->dataProvider->getPlanJourney($entryPointDefinitionStart, $entryPointDefinitionArrival, $isStartTime, $date, $this->piVars['criteria'], $this->nbBefore, $this->nbAfter, $binaryCriteria);
			
			$planJourneyResults = t3lib_div::makeInstance('tx_icsnavitiajourney_results', $this);
			
			if (intval($this->nbBefore) > 0 && intval($this->nbAfter) > 0) {
				$planJourneyResults = t3lib_div::makeInstance('tx_icsnavitiajourney_results', $this);
				$content = $planJourneyResults->getPlanJourneyResults($planJourney, $params);
			}
			else {
				$planJourneyDetails = t3lib_div::makeInstance('tx_icsnavitiajourney_details', $this);
				$content = $planJourneyDetails->getPlanJourneyDetails($this->dataProvider, $planJourney, $params);
			}
		}
		else {
			$search = t3lib_div::makeInstance('tx_icsnavitiajourney_search', $this);
			$content = $search->getSearchForm($this->dataProvider, $entryPointStart, $entryPointArrival);
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	private function init() {
		$this->login = $this->conf['login'];
		$this->url = $this->conf['url'];
		
		$this->dataProvider = t3lib_div::makeInstance('tx_icslibnavitia_APIService', $this->url, $this->login);
		$this->pictoLine = t3lib_div::makeInstance('tx_icslinepicto_getlines');
		$templateflex_file = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 'configuration');
		$this->templates = array(
			'search' => $this->cObj->fileResource($templateflex_file?'uploads/tx_icsnavitiajourney/' . $templateflex_file:$this->conf['view.']['search.']['templateFile']),
			'results' => $this->cObj->fileResource($templateflex_file?'uploads/tx_icsnavitiajourney/' . $templateflex_file:$this->conf['view.']['results.']['templateFile']),
			'details' => $this->cObj->fileResource($templateflex_file?'uploads/tx_icsnavitiajourney/' . $templateflex_file:$this->conf['view.']['details.']['templateFile'])
		);
		
		$libnavitia_conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ics_libnavitia']);
		$this->debug_param = $libnavitia_conf['debug_param'];
		
		if (isset($this->piVars['nbBefore'])) {
			$this->nbBefore = $this->piVars['nbBefore'];
		}
		elseif (isset($this->conf['nbBefore'])) {
			$this->nbBefore = $this->conf['nbBefore'];
		}
		else {
			$this->nbBefore = 1;
		}
		
		if (isset($this->piVars['nbAfter'])) {
			$this->nbAfter = $this->piVars['nbAfter'];
		}
		elseif (isset($this->conf['nbBefore'])) {
			$this->nbAfter = $this->conf['nbAfter'];
		}
		else {
			$this->nbAfter = 1;
		}
		$this->includeLibJS();
	}
	
	public function getHiddenFields() {
		$params = t3lib_div::_GET();
		$arguments = array();
		foreach ($params as $name => $value) {
			if (is_array($value))
				continue;
			if ((strpos($name, 'tx_') === 0) || (strpos($name, 'user_') === 0))
				continue;
			$arguments[$name] = strval($value);
		}
		$hidden = '';
		foreach ($arguments as $name => $value)
			$hidden .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />';
		return $hidden;
	}
	
	public function replaceModes($template) {
		$modes = $this->dataProvider->getModeTypeList();

		$modeTypeListTemplate = $this->cObj->getSubpart($template, '###MODE_TYPE_LIST###');
		$modeTypeListContent = '';
		$idx = 0;
		foreach ($modes->ToArray() as $mode) {
			if (empty($mode->externalCode) || $mode->externalCode == 'Indéfini')
				continue;
			$markers = array();
			$markers['VALUE'] = $mode->externalCode;
			$markers['NAME'] = $mode->name;
			$markers['IDX'] = $idx++;
			$markers['SELECTED'] = (!isset($this->piVars['mode']) || in_array($mode->externalCode, $this->piVars['mode'])) ? ' checked="checked"' : '';
			$modeTypeListContent .= $this->cObj->substituteMarkerArray($modeTypeListTemplate, $markers, '###|###');
		}
		return $this->cObj->substituteSubpart($template, '###MODE_TYPE_LIST###', $modeTypeListContent);
	}
	
	public function replaceCriteria($template) {
		$criterias = array();
		for ($i = 1; $i < 5; $i++)
			$criterias[] = array(
				'value' => $i,
				'name' => $this->pi_getLL('preference_criteria_' . $i),
			);

		$criteriaListTemplate = $this->cObj->getSubpart($template, '###CRITERIA_LIST###');
		$criteriaListContent = '';
		foreach ($criterias as $criteria)  {
			$markers = array();
			$markers['VALUE'] = $criteria['value'];
			$markers['NAME'] = $criteria['name'];
			if (($criteria['value'] == $this->piVars['criteria']) ||
				(!isset($this->piVars['criteria']) && ($criteria['value'] == 1))) {
				$markers['SELECTED'] = ' checked="checked"';
			}
			else {
				$markers['SELECTED'] = '';
			}
			$criteriaListContent .= $this->cObj->substituteMarkerArray($criteriaListTemplate, $markers, '###|###');
		}
		
		return $this->cObj->substituteSubpart($template, '###CRITERIA_LIST###', $criteriaListContent);
	}
	
	private function includeLibJS() {
		if ($this->jsIncluded)
			return;
		$file = t3lib_div::resolveBackPath($GLOBALS['TSFE']->tmpl->getFileName('EXT:' . $this->extKey . '/res/js/localize.js'));
		$file = t3lib_div::createVersionNumberedFilename($file);
		$tag = '	<script src="' . htmlspecialchars($file) . '" type="text/javascript"></script>' . PHP_EOL;
		$GLOBALS['TSFE']->additionalHeaderData['geoloc'] = $tag;
		$this->jsIncluded = true;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_navitia_journey/pi1/class.tx_icsnavitiajourney_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_navitia_journey/pi1/class.tx_icsnavitiajourney_pi1.php']);
}

?>