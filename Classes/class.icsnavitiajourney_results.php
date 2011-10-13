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
 
class tx_icsnavitiajourney_results {

	public function __construct($pObj) {
		$this->pObj = $pObj;
	}

	public function getPlanJourneyResults($journeyPlan, $callParams) {
		$templatePart = $this->pObj->templates['results'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_RESULTS###');
		
		$markers = array(
			'PREFIXID' => htmlspecialchars($this->pObj->prefixId),
			'FROM' => htmlspecialchars($this->pObj->pi_getLL('from')),
			'TO' => htmlspecialchars($this->pObj->pi_getLL('to')),
			'SEARCH' => htmlspecialchars($this->pObj->pi_getLL('menu_search')),
			'SEARCH_LINK' => $this->pObj->pi_getPageLink($GLOBALS['TSFE']->id),
			'RESULTS' => htmlspecialchars($this->pObj->pi_getLL('menu_results')),
			'DETAILS' => htmlspecialchars($this->pObj->pi_getLL('menu_details')),
			'STOP_START' => htmlspecialchars($callParams['startName']),
			'CITY_START' => htmlspecialchars($callParams['startCity']),
			'STOP_ARRIVAL' => htmlspecialchars($callParams['arrivalName']),
			'CITY_ARRIVAL' => htmlspecialchars($callParams['arrivalCity']),
			'START_TO' => htmlspecialchars($this->pObj->pi_getLL('startAt')),
			'ARRIVAL_TO' => htmlspecialchars($this->pObj->pi_getLL('arrivalAt')),
			'PREFERENCES' => htmlspecialchars($this->pObj->pi_getLL('preference')),
			'MODE_TYPE' => htmlspecialchars($this->pObj->pi_getLL('preference_mode')),
			'CRITERIA' => htmlspecialchars($this->pObj->pi_getLL('preference_criteria')),
			'RESULT_HOURS_START' => '',
			'AND' => '',
			'FIRST_HOUR' => '',
			'LAST_HOUR' => '',
			'START_HOUR' => '',
			'ARRIVAL_HOUR' => '',
			'PREVIOUS_JOURNEY' => htmlspecialchars($this->pObj->pi_getLL('results_previous')),
			'NEXT_JOURNEY' => htmlspecialchars($this->pObj->pi_getLL('results_next')),
			'SELECTED_0' => '',
			'SELECTED_1' => '',
			'ACTION_URL' => htmlspecialchars($this->pObj->pi_linkTP_keepPIvars_url()),
			'HIDDEN_FIELDS' => $this->pObj->getHiddenFields(),
			'NOTA' => '',
			'DATE'=> $this->pObj->pi_getLL('results_date'),
			'SUBMIT' => $this->pObj->pi_getLL('results_submit'),
		);
		$params = t3lib_div::_GET($this->pObj->prefixId);
		foreach ($params as $name => $value) {
			if (is_array($value))
				continue;
			if (in_array($name, array('isStartTime', 'date', 'hour', 'mode', 'criteria')))
				continue;
			$markers['HIDDEN_FIELDS'] .= '<input type="hidden" name="' . htmlspecialchars($this->pObj->prefixId) . '[' . htmlspecialchars($name) . ']" value="' . htmlspecialchars($value) . '" />';
		}
		
		if(isset($this->pObj->piVars['isStartTime'])) {
			$markers['SELECTED_' . $this->pObj->piVars['isStartTime']] = ' checked';
		}
		else {
			$markers['SELECTED_1'] = ' checked';
		}
		
		$markers['DATE_SEL'] = $this->pObj->piVars['date'];
		$markers['HOUR_SEL'] = $this->pObj->piVars['hour'];

		$template = $this->pObj->replaceModes($template);
		$template = $this->pObj->replaceCriteria($template);

		if (!is_null($journeyPlan['JourneyResultList']->Get(0)->summary)) {
		
			if($this->pObj->piVars['isStartTime']) {
				$markers['RESULT_HOURS_START'] = htmlspecialchars($this->pObj->pi_getLL('result_hour_start'));
			}
			else {
				$markers['RESULT_HOURS_START'] = htmlspecialchars($this->pObj->pi_getLL('result_hour_arrival'));
			}
			
			$markers['AND'] = htmlspecialchars($this->pObj->pi_getLL('and'));
			$markers['FIRST_HOUR'] = $journeyPlan['JourneyResultList']->Get(0)->summary->departure->format('H:i'); // TODO: Hour format as config.
			$markers['LAST_HOUR'] = $journeyPlan['JourneyResultList']->Get(intval($journeyPlan['JourneyResultList']->Count()-1))->summary->departure->format('H:i');
			
			$markers['PREVIOUS_JOURNEY_LINK'] = $this->pObj->pi_linkTP_keepPIvars_url(
				array(
					'date' 		=> $journeyPlan['JourneyResultList']->Get(0)->summary->departure->format('d/m/Y'),  // TODO: Use Call.
					'hour' 		=> $journeyPlan['JourneyResultList']->Get(0)->summary->departure->format('H:i')
				)
			);
			
			$markers['NEXT_JOURNEY_LINK'] = $this->pObj->pi_linkTP_keepPIvars_url(
				array(
					'date' 		=> $journeyPlan['JourneyResultList']->Get($journeyPlan['JourneyResultList']->Count()-1)->summary->departure->format('d/m/Y'),  // TODO: Use Call.
					'hour' 		=> $journeyPlan['JourneyResultList']->Get($journeyPlan['JourneyResultList']->Count()-1)->summary->departure->format('H:i')
				)
			);
		}
		$useBound = true;
		$template = $this->pObj->cObj->substituteSubpart($template, '###RESULTS_LIST###', $this->renderResults($template, $journeyPlan['JourneyResultList'], $useBound));
		
		if($journeyPlan['JourneyResultList']->Get(0)->sections->Get(0)->nota->type) {
			$markers['NOTA'] = $journeyPlan['JourneyResultList']->Get(0)->sections->Get(0)->nota->type; // TODO : Tableau associatif code erreur // phrase
		}

		$content = $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $content;
	}
	
	private function renderResults($template, tx_icslibnavitia_INodeList $results, $useBound = true) {
		$linePicto = t3lib_div::makeInstance('tx_icslinepicto_getlines');
		$resultListTemplate = $this->pObj->cObj->getSubpart($template, '###RESULTS_LIST###');
		$resultListContent = '';
		
		foreach ($results->ToArray() as $journeyResult) {
			$markers = array(
				'DETAILS_URL'		=> '',
				'START_HOUR'		=>'',
				'ARRIVAL_HOUR'		 => '',
				'DURATION'			 => '',
				'PICTOS'			 => '',
				'BEST'				 => '',
			);
		
			if($journeyResult->best) {
				$markers['BEST'] = $this->pObj->prefixId . '_best';
			}
		
			if (!is_null($journeyResult->summary)) {
				$markers['DETAILS_URL'] = $this->pObj->pi_linkTP_keepPIvars_url(
					array(
						'nbBefore' 	=> '0',
						'nbAfter' 	=> '0',
						'date' 		=> $journeyResult->summary->departure->format('d/m/Y'), 
						'hour' 		=> $journeyResult->summary->departure->format('H:i')
					)
				);
				$markers['START_HOUR'] = $journeyResult->summary->departure->format('H:i');
				$markers['ARRIVAL_HOUR'] = $journeyResult->summary->arrival->format('H:i');
			}
			elseif($results->Count() == 1) {
				continue;
			}

			$index = 0;
			foreach ($journeyResult->sections->ToArray() as $section) {
			
				$duration = '';
				
				if($journeyResult->summary->duration->day) {
					$duration .= $journeyResult->summary->duration->day . ' ' . $this->pObj->pi_getLL('day') . ' ';
				}
				
				if($journeyResult->summary->duration->hour) {
					$duration .= $journeyResult->summary->duration->hour . ' ' . $this->pObj->pi_getLL('hour') . ' ';
				}
				
				if($journeyResult->summary->duration->minute) {
					$duration .= $journeyResult->summary->duration->minute . ' ' . $this->pObj->pi_getLL('minute') . ' ';
				}
				
				$markers['DURATION'] = $duration;
				
				$confImg = array();
				/*switch($section->type) {
					case 'VehicleJourneyConnection' :
						$aPicto[] = strtolower($section->vehicleJourney->route->line->modeType->externalCode);
						$confImg['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/' . $this->aPicto[strtolower($section->vehicleJourney->route->line->modeType->externalCode)];
						break;
					default :
						$aPicto[] = 'walk';
						if(!$index || ($index && $aPicto[$index-1] != 'walk')) {
							$confImg['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/' . $this->aPicto['walk'];
						}
					break;
				}*/
				
				if($this->pObj->conf['icons.'][$section->type] != 'CASE') {
					if($useBound && $this->pObj->conf['icons.'][$section->type . '.']['onlyBounds']) {
						if($index == 0 || ($index == $journeyResult->sections->Count()-1)) {
							$confImg['file'] = $this->pObj->conf['icons.'][$section->type];
						}
						else {
							$confImg['file'] = null;
						}
					}
					else {
						$confImg['file'] = $this->pObj->conf['icons.'][$section->type];
					}
				}
				else {
					$aKey = explode('|', $this->pObj->conf['icons.'][$section->type . '.']['key']);
					$sectionObj = $section;
					for($i=0;$i<count($aKey);$i++) {
						$sectionObj = $this->getObject($sectionObj, $aKey[$i]);
					}
					$confImg['file'] = $this->pObj->conf['icons.'][$section->type . '.'][iconv("UTF-8", "ASCII//TRANSLIT", $sectionObj)];
				}
				
				if(!empty($confImg['file'])) {
					$markers['PICTOS'] .= $this->pObj->cObj->IMAGE($confImg);
				}
				$index++;
			}
			$resultListContent .= $this->pObj->cObj->substituteMarkerArray($resultListTemplate, $markers, '###|###');
		}
		return $resultListContent;
	}
	
	function getObject($object, $key) {
		return $object->$key;
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