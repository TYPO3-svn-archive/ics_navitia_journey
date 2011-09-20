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

		if($this->pObj->debug) {
			$this->debugParam = t3lib_div::_GP($this->pObj->debug_param);
		}
		
		ini_set('display_errors', 1);
		
		//t3lib_div::devlog('Appel pi1 journey', 'ics_navitia_journey', 0, $this->pObj->piVars);
		
		$markers = array(
			'###PREFIXID###' => $this->pObj->prefixId,
			'###SEARCH###' => $this->pObj->pi_getLL('menu.search'),
			'###RESULTS###' => $this->pObj->pi_getLL('menu.results'),
			'###DETAILS###' => $this->pObj->pi_getLL('menu.details'),
			'###ACTION_URL###' => $this->pObj->pi_getPageLink($GLOBALS['TSFE']->id),//$this->pObj->pi_linkTP_keepPIvars_url(),
			'###START_ADDRESS###' => $this->pObj->pi_getLL('search.startAddress'),
			'###ARRIVAL_ADDRESS###' => $this->pObj->pi_getLL('search.arrivalAddress'),
			'###MY_POSITION_TEXT###' => $this->pObj->pi_getLL('search.myPosition'),
			'###NAME###' => $this->pObj->pi_getLL('search.name'),
			'###CITY###' => $this->pObj->pi_getLL('search.city'),
			'###JOURNEY_DATE###' => $this->pObj->pi_getLL('search.journeyDate'),
			'###START_AT###' => $this->pObj->pi_getLL('startAt'),
			'###ARRIVAL_AT###' => $this->pObj->pi_getLL('arrivalAt'),
			'###PREFERENCES###' => $this->pObj->pi_getLL('preference'),
			'###MODE_TYPE###' => $this->pObj->pi_getLL('preference.mode'),
			'###CRITERIA###' => $this->pObj->pi_getLL('preference.criteria'),
			'###SUBMIT###' => $this->pObj->pi_getLL('search.submit'),
			'###START_NAME_VALUE###' => $this->pObj->piVars['startName'],
			'###ARRIVAL_NAME_VALUE###' => $this->pObj->piVars['arrivalName'],
			'###START_CITY_VALUE###' => $this->pObj->piVars['startCity'],
			'###ARRIVAL_CITY_VALUE###' => $this->pObj->piVars['arrivalCity'],
			'###SELECTED_0###' => '',
			'###SELECTED_1###' => '',
		);
		
		$markers['###SELECTED_' . $this->pObj->piVars['isStartTime'] . '###'] = 'selected="selected"';
		
		if($entryPointStart && $entryPointArrival) {
			if($entryPointStart->Count() == 0) {
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_START###', '');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_START###', '');
				$noSolutionTemplate = $this->pObj->cObj->getSubpart($template, '###CONFIRM_NO_SOLUTION_START###');
				$markers['###START_NO_SOLUTION###'] = $this->pObj->pi_getLL('nosolution');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_START###', $noSolutionTemplate);
			}
			elseif($entryPointStart->Count() == 1) {
				$entryPoint = $entryPointStart->Get(0);

				$oneSolutionTemplate = $this->pObj->cObj->getSubpart($template, '###CONFIRM_ONE_SOLUTION_START###');
				
				$markers['###START_ONE_SOLUTION###'] = $this->pObj->pi_getLL('onesolution');
				$markers['###CITY_START###'] = $entryPoint->cityName;
				
				$stop = $entryPoint->{lcfirst($entryPoint->type)};
				$markers['###STOPPOINT_START###'] = $stop->name;
				
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_START###', $oneSolutionTemplate);
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_START###', '');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_START###', '');
			}
			else {
				$moreSolutionsTemplate = $this->pObj->cObj->getSubpart($template, '###CONFIRM_MORE_SOLUTIONS_START###');
				$markers['###START_MORE_SOLUTIONS###'] = $this->pObj->pi_getLL('moresolutions');
				$index = 0;
				foreach($entryPointStart->ToArray() as $entryPoint) {
					$solutionsItemTemplate = $this->pObj->cObj->getSubpart($template, '###LIST_MORE_SOLUTIONS_START###');
					$stop = $entryPoint->{lcfirst($entryPoint->type)};
					$markers['###ENTRYPOINT_START###'] = $index;
					$markers['###CITY_START###'] = $entryPoint->cityName;
					$markers['###STOPPOINT_START###'] = $stop->name;
					$markers['###RECORDNUMBER###'] = $index;
					$markers['###ENTRYPOINT_TYPE_START###'] = $this->pObj->pi_getLL('entryPointType.' . strtolower($entryPoint->type));
					$index++;
					$solutionItem .= $this->pObj->cObj->substituteMarkerArray($solutionsItemTemplate, $markers);
				}
				
				$moreSolutionsTemplate = $this->pObj->cObj->substituteSubpart($moreSolutionsTemplate, '###LIST_MORE_SOLUTIONS_START###', $solutionItem);
				$moreSolutionContent = $this->pObj->cObj->substituteMarkerArray($moreSolutionsTemplate, $markers);
				
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_START###', $moreSolutionContent);
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_START###', '');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_START###', '');
			}
			
			if($entryPointArrival->Count() == 0) {
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_ARRIVAL###', '');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_ARRIVAL###', '');
				$noSolutionTemplate = $this->pObj->cObj->getSubpart($template, '###CONFIRM_NO_SOLUTION_ARRIVAL###');
				$markers['###ARRIVAL_NO_SOLUTION###'] = $this->pObj->pi_getLL('nosolution');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_ARRIVAL###', $noSolutionTemplate);
			}
			elseif($entryPointArrival->Count() == 1) {
				$entryPoint = $entryPointArrival->Get(0);

				$oneSolutionTemplate = $this->pObj->cObj->getSubpart($template, '###CONFIRM_ONE_SOLUTION_ARRIVAL###');
				
				$markers['###ARRIVAL_ONE_SOLUTION###'] = $this->pObj->pi_getLL('onesolution');
				$markers['###CITY_ARRIVAL###'] = $entryPoint->cityName;
				
				$stop = $entryPoint->{lcfirst($entryPoint->type)};
				$markers['###STOPPOINT_ARRIVAL###'] = $stop->name;
				
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_ARRIVAL###', $oneSolutionTemplate);
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_ARRIVAL###', '');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_ARRIVAL###', '');
			}
			else {
				$moreSolutionsTemplate = $this->pObj->cObj->getSubpart($template, '###CONFIRM_MORE_SOLUTIONS_ARRIVAL###');
				$markers['###ARRIVAL_MORE_SOLUTIONS###'] = $this->pObj->pi_getLL('moresolutions');
				$index = 0;
				foreach($entryPointArrival->ToArray() as $entryPoint) {
					$solutionsItemTemplate = $this->pObj->cObj->getSubpart($template, '###LIST_MORE_SOLUTIONS_ARRIVAL###');
					$stop = $entryPoint->{lcfirst($entryPoint->type)};
					$markers['###ENTRYPOINT_ARRIVAL###'] = $index;
					$markers['###CITY_ARRIVAL###'] = $entryPoint->cityName;
					$markers['###STOPPOINT_ARRIVAL###'] = $stop->name;
					$markers['###RECORDNUMBER###'] = $index;
					
					$markers['###ENTRYPOINT_TYPE_ARRIVAL###'] = $this->pObj->pi_getLL('entryPointType.' . strtolower($entryPoint->type));
					$index++;
					$solutionItemArrival .= $this->pObj->cObj->substituteMarkerArray($solutionsItemTemplate, $markers);
				}
				
				$moreSolutionsTemplate = $this->pObj->cObj->substituteSubpart($moreSolutionsTemplate, '###LIST_MORE_SOLUTIONS_ARRIVAL###', $solutionItemArrival);
				$moreSolutionContent = $this->pObj->cObj->substituteMarkerArray($moreSolutionsTemplate, $markers);
				
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_ARRIVAL###', $moreSolutionContent);
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_ARRIVAL###', '');
				$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_ARRIVAL###', '');
			}
			
		}
		else {
			$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_START###', '');
			$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_START###', '');
			$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_START###', '');
			
			$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_MORE_SOLUTIONS_ARRIVAL###', '');
			$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_ONE_SOLUTION_ARRIVAL###', '');
			$template = $this->pObj->cObj->substituteSubpart($template, '###CONFIRM_NO_SOLUTION_ARRIVAL###', '');
		}
		
		$markers['###DATE_SEL###'] = date('d/m/Y');
		$markers['###TIME_SEL###'] = date('H:i');
		
		if(!is_null($this->debugParam)) {
			$markers['###ACTION_URL###'] .= '&' . $this->pObj->debug_param . '=' . $this->debugParam;
		}
		
		if($this->pObj->debug) {
			$this->debugParam = t3lib_div::_GP($this->pObj->debug_param);
		}
		
		$markers['###URL###'] .= '&' . $this->pObj->debug_param . '=' . $this->debugParam;
		
		$aModesType = array(
			0 => array('ModeTypeExternalCode' => 'Bus', 'ModeTypeName' => 'Bus'),
			1 => array('ModeTypeExternalCode' => 'métro', 'ModeTypeName' => 'métro')
		); // A remplacer par la méthode qui récupère les types de mode.
		
		$modeTypeListTemplate = $this->pObj->cObj->getSubpart($template, '###MODE_TYPE_LIST###');
		foreach($aModesType as $mode)  {
			$markers['###MODE_TYPE_VALUE###'] = $mode['ModeTypeExternalCode'];
			$markers['###MODE_TYPE_NAME###'] = $mode['ModeTypeName'];
			$markers['###SELECTED_' . $mode['ModeTypeExternalCode'] . '###'] = 'checked'; // à modifier si on ne veut pas que tous les modes soient actifs par défaut
			$modeTypeListContent .= $this->pObj->cObj->substituteMarkerArray($modeTypeListTemplate, $markers);
		}
		
		$aCriteria = array(
			0 => array(
				'criteriaValue' => 1,
				'criteriaName' => $this->pObj->pi_getLL('preference.criteria.1'),
			),
			1 => array(
				'criteriaValue' => 2,
				'criteriaName' => $this->pObj->pi_getLL('preference.criteria.2'),
			),
			2 => array(
				'criteriaValue' => 3,
				'criteriaName' => $this->pObj->pi_getLL('preference.criteria.3'),
			),
			3 => array(
				'criteriaValue' => 4,
				'criteriaName' => $this->pObj->pi_getLL('preference.criteria.4'),
			)
		); // A modifier
		
		

		$criteriaListTemplate = $this->pObj->cObj->getSubpart($template, '###CRITERIA_LIST###');
		foreach($aCriteria as $criteria)  {
			$markers['###CRITERIA_VALUE###'] = $criteria['criteriaValue'];
			$markers['###CRITERIA_NAME###'] = $criteria['criteriaName'];
			
			if($criteria['criteriaValue'] == 1) {
				$markers['###SELECTED_criteria_' . $criteria['criteriaValue'] . '###'] = 'checked'; // à modifier
			}
			else {
				$markers['###SELECTED_criteria_' . $criteria['criteriaValue'] . '###'] = '';
			}
			$criteriaListContent .= $this->pObj->cObj->substituteMarkerArray($criteriaListTemplate, $markers);
		}
		
		//var_dump($criteriaListContent);
		
		$template = $this->pObj->cObj->substituteSubpart($template, '###CRITERIA_LIST###', $criteriaListContent);
		$template = $this->pObj->cObj->substituteSubpart($template, '###MODE_TYPE_LIST###', $modeTypeListContent);
		$content .= $this->pObj->cObj->substituteMarkerArray($template, $markers);
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