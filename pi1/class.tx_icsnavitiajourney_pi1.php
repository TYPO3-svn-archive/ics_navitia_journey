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
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		$this->init();
		
		if (is_numeric($this->piVars['entryPointStart']) && is_numeric($this->piVars['entryPointArrival'])) {
			$entryPointStart = $this->dataProvider->getEntryPointListByNameAndCity(($this->piVars['startName']), ($this->piVars['startCity']));
			$entryPointArrival = $this->dataProvider->getEntryPointListByNameAndCity(($this->piVars['arrivalName']), ($this->piVars['arrivalCity']));
			
			$entryPointFinalStart = $entryPointStart->Get(intval($this->piVars['entryPointStart']));
			$entryPointFinalArrival = $entryPointArrival->Get(intval($this->piVars['entryPointArrival']));
			
			$entryPointDefinitionStart = tx_icslibnavitia_EntryPointDefinition::fromEntryPoint($entryPointFinalStart);
			$entryPointDefinitionArrival = tx_icslibnavitia_EntryPointDefinition::fromEntryPoint($entryPointFinalArrival);
			
			t3lib_div::devlog('Appel pi1 journey results', 'ics_navitia_journey', 0, array(intval($this->piVars['entryPointStart']), intval($this->piVars['entryPointArrival'])));
			
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
			
			$planJourney = $this->dataProvider->getPlanJourney($entryPointDefinitionStart, $entryPointDefinitionArrival, $isStartTime, $date, $this->piVars['criteria'], $this->nbBefore, $this->nbAfter);
			
			$planJourneyResults = t3lib_div::makeInstance('tx_icsnavitiajourney_results', $this);
			
			if (intval($this->nbBefore) > 0 && intval($this->nbAfter) > 0) {
				$planJourneyResults = t3lib_div::makeInstance('tx_icsnavitiajourney_results', $this);
				$content = $planJourneyResults->getPlanJourneyResults($planJourney, $params);
			}
			else {
				$planJourneyDetails = t3lib_div::makeInstance('tx_icsnavitiajourney_details', $this);
				$content = $planJourneyDetails->getPlanJourneyDetails($planJourney, $params);	
			}
		}
		elseif ($this->piVars['searchSubmit']) {
			$entryPointStart = $this->dataProvider->getEntryPointListByNameAndCity($this->piVars['startName'], $this->piVars['startCity']);
			$entryPointArrival = $this->dataProvider->getEntryPointListByNameAndCity($this->piVars['arrivalName'], $this->piVars['arrivalCity']);
			
			$search = t3lib_div::makeInstance('tx_icsnavitiajourney_search', $this);
			$content = $search->getSearchForm($this->dataProvider, $entryPointStart, $entryPointArrival);
		}
		else {
			$search = t3lib_div::makeInstance('tx_icsnavitiajourney_search', $this);
			$content = $search->getSearchForm($this->dataProvider);
		}
		
		return $this->pi_wrapInBaseClass($content);
	}
	
	function init() {
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
		$aModesType = array(
			0 => array('ModeTypeExternalCode' => 'Bus', 'ModeTypeName' => 'Bus'),
			1 => array('ModeTypeExternalCode' => 'métro', 'ModeTypeName' => 'métro')
		); // TODO: ModeType retrieval from API. Create the necessary API method.
		
		$modeTypeListTemplate = $this->cObj->getSubpart($template, '###MODE_TYPE_LIST###');
		$modeTypeListContent = '';
		foreach ($aModesType as $mode) {
			$markers = array();
			$markers['MODE_TYPE_VALUE'] = $mode['ModeTypeExternalCode'];
			$markers['MODE_TYPE_NAME'] = $mode['ModeTypeName'];
			$markers['SELECTED_' . $mode['ModeTypeExternalCode']] = ' checked="checked"'; // TODO: Default value and read from parameters.
			$modeTypeListContent .= $this->cObj->substituteMarkerArray($modeTypeListTemplate, $markers, '###|###');
		}
		return $this->cObj->substituteSubpart($template, '###MODE_TYPE_LIST###', $modeTypeListContent);
	}
	
	public function replaceCriteria($template) {
		$aCriteria = array(
			0 => array(
				'criteriaValue' => 1,
				'criteriaName' => $this->pi_getLL('preference.criteria.1'),
			),
			1 => array(
				'criteriaValue' => 2,
				'criteriaName' => $this->pi_getLL('preference.criteria.2'),
			),
			2 => array(
				'criteriaValue' => 3,
				'criteriaName' => $this->pi_getLL('preference.criteria.3'),
			),
			3 => array(
				'criteriaValue' => 4,
				'criteriaName' => $this->pi_getLL('preference.criteria.4'),
			)
		); // TODO: Loop from 1 to 4.

		$criteriaListTemplate = $this->cObj->getSubpart($template, '###CRITERIA_LIST###');
		$criteriaListContent = '';
		foreach ($aCriteria as $criteria)  {
			$markers['CRITERIA_VALUE'] = $criteria['criteriaValue'];
			$markers['CRITERIA_NAME'] = $criteria['criteriaName'];
			if ($criteria['criteriaValue'] == 1) {
				$markers['SELECTED_criteria_' . $criteria['criteriaValue']] = ' checked="checked"'; // TODO: Default value and read from parameters.
			}
			else {
				$markers['SELECTED_criteria_' . $criteria['criteriaValue']] = '';
			}
			$criteriaListContent .= $this->cObj->substituteMarkerArray($criteriaListTemplate, $markers, '###|###');
		}
		
		return $this->cObj->substituteSubpart($template, '###CRITERIA_LIST###', $criteriaListContent);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_navitia_journey/pi1/class.tx_icsnavitiajourney_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ics_navitia_journey/pi1/class.tx_icsnavitiajourney_pi1.php']);
}

?>