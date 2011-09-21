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
 
class tx_icsnavitiajourney_search {

	public function __construct($pObj) {
		$this->pObj = $pObj;
	}

	function getSearchForm($dataProvider = null, $entryPointStart = null, $entryPointArrival = null) {
		$templatePart = $this->pObj->templates['search'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_SEARCH###');
		
		$markers = array(
			'PREFIXID' => htmlspecialchars($this->pObj->prefixId),
			'SEARCH' => htmlspecialchars($this->pObj->pi_getLL('menu.search')),
			'RESULTS' => htmlspecialchars($this->pObj->pi_getLL('menu.results')),
			'DETAILS' => htmlspecialchars($this->pObj->pi_getLL('menu.details')),
			'ACTION_URL' => htmlspecialchars($this->pObj->pi_getPageLink($GLOBALS['TSFE']->id)),
			'START_ADDRESS' => htmlspecialchars($this->pObj->pi_getLL('search.startAddress')),
			'ARRIVAL_ADDRESS' => htmlspecialchars($this->pObj->pi_getLL('search.arrivalAddress')),
			'MY_POSITION_TEXT' => htmlspecialchars($this->pObj->pi_getLL('search.myPosition')),
			'NAME' => htmlspecialchars($this->pObj->pi_getLL('search.name')),
			'CITY' => htmlspecialchars($this->pObj->pi_getLL('search.city')),
			'JOURNEY_DATE' => htmlspecialchars($this->pObj->pi_getLL('search.journeyDate')),
			'START_AT' => htmlspecialchars($this->pObj->pi_getLL('startAt')),
			'ARRIVAL_AT' => htmlspecialchars($this->pObj->pi_getLL('arrivalAt')),
			'PREFERENCES' => htmlspecialchars($this->pObj->pi_getLL('preference')),
			'MODE_TYPE' => htmlspecialchars($this->pObj->pi_getLL('preference.mode')),
			'CRITERIA' => htmlspecialchars($this->pObj->pi_getLL('preference.criteria')),
			'SUBMIT' => htmlspecialchars($this->pObj->pi_getLL('search.submit')),
			'START_NAME_VALUE' => htmlspecialchars($this->pObj->piVars['startName']),
			'ARRIVAL_NAME_VALUE' => htmlspecialchars($this->pObj->piVars['arrivalName']),
			'START_CITY_VALUE' => htmlspecialchars($this->pObj->piVars['startCity']),
			'ARRIVAL_CITY_VALUE' => htmlspecialchars($this->pObj->piVars['arrivalCity']),
			'SELECTED_0' => '',
			'SELECTED_1' => '',
			'HIDDEN_FIELDS' => $this->pObj->getHiddenFields(),
		);
		
		$markers['SELECTED_' . $this->pObj->piVars['isStartTime']] = ' selected="selected"';
		
		$template = $this->pObj->cObj->substituteSubpart(
			$template, 
			'###CONFIRM_SOLUTIONS_START###',
			$this->makeSolutionPart($entryPointStart, true)
		);
		$template = $this->pObj->cObj->substituteSubpart(
			$template, 
			'###CONFIRM_SOLUTIONS_ARRIVAL###',
			$this->makeSolutionPart($entryPointArrival, false)
		);
		
		$markers['DATE_SEL'] = htmlspecialchars(date('d/m/Y'));
		$markers['TIME_SEL'] = htmlspecialchars(date('H:i'));

		$template = $this->pObj->replaceModes($template);
		$template = $this->pObj->replaceCriteria($template);

		$content .= $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $content;
	}
	
	private function makeSolutionPart($entryPoint, $isStart) {
		$content = '';
		$markers = array(
			'NAME_SUFFIX' => ($isStart ? 'Start' : 'Arrival')
		);
		if ($entryPoint != null) {
			if ($entryPoint->Count() == 0) {
				$noSolutionTemplate = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###CONFIRM_NO_SOLUTION###');
				$markers['NO_SOLUTION'] = $this->pObj->pi_getLL('nosolution');
				$content = $this->pObj->cObj->substituteMarkerArray($noSolutionTemplate, $markers, '###|###');
			}
			elseif ($entryPoint->Count() == 1) {
				$entryPoint = $entryPoint->Get(0);
				$oneSolutionTemplate = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###CONFIRM_ONE_SOLUTION###');
				
				$markers['ONE_SOLUTION'] = $this->pObj->pi_getLL('onesolution');
				$markers['CITY'] = $entryPoint->cityName;
				
				$stop = $entryPoint->{lcfirst($entryPoint->type)};
				$markers['STOPPOINT'] = $stop->name;
				
				$content = $this->pObj->cObj->substituteMarkerArray($oneSolutionTemplate, $markers, '###|###');
			}
			else {
				$moreSolutionsTemplate = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###CONFIRM_MORE_SOLUTIONS###');
				$markers['MORE_SOLUTIONS'] = $this->pObj->pi_getLL('moresolutions');
				$index = 0;
				$solutionItem = '';
				foreach ($entryPoint->ToArray() as $entryPoint) {
					$solutionsItemTemplate = $this->pObj->cObj->getSubpart($moreSolutionsTemplate, '###LIST_MORE_SOLUTIONS###');
					$stop = $entryPoint->{lcfirst($entryPoint->type)};
					$locMarkers = array();
					$locMarkers['ENTRYPOINT'] = $index;
					$locMarkers['CITY'] = $entryPoint->cityName;
					$locMarkers['STOPPOINT'] = $stop->name;
					$locMarkers['RECORDNUMBER'] = $index;
					$locMarkers['ENTRYPOINT_TYPE'] = $this->pObj->pi_getLL('entryPointType.' . strtolower($entryPoint->type));
					$index++;
					$solutionItem .= $this->pObj->cObj->substituteMarkerArray($solutionsItemTemplate, $locMarkers, '###|###');
				}
				$moreSolutionsTemplate = $this->pObj->cObj->substituteSubPart($moreSolutionsTemplate, '###LIST_MORE_SOLUTIONS###', $solutionItem);
				$content = $this->pObj->cObj->substituteMarkerArray($moreSolutionsTemplate, $markers, '###|###');
			}
		}
		return $content;
	}
}

if (!function_exists('lcfirst')) {
	function lcfirst($str) {
		if (strlen($str) > 0) {
			$str = strtolower($str{0}) . substr($str, 1);
		}
		return $str;
	}
}