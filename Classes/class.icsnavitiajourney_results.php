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

	function getPlanJourneyResults($journeyPlan, $params) {
		$templatePart = $this->pObj->templates['results'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_RESULTS###');
		
		$markers = array(
			'PREFIXID' => htmlspecialchars($this->pObj->prefixId),
			'FROM' => htmlspecialchars($this->pObj->pi_getLL('from')),
			'TO' => htmlspecialchars($this->pObj->pi_getLL('to')),
			'SEARCH' => htmlspecialchars($this->pObj->pi_getLL('menu.search')),
			'RESULTS' => htmlspecialchars($this->pObj->pi_getLL('menu.results')),
			'DETAILS' => htmlspecialchars($this->pObj->pi_getLL('menu.details')),
			'STOP_START' => htmlspecialchars($params['startName']),
			'CITY_START' => htmlspecialchars($params['startCity']),
			'STOP_ARRIVAL' => htmlspecialchars($params['arrivalName']),
			'CITY_ARRIVAL' => htmlspecialchars($params['arrivalCity']),
			'START_TO' => htmlspecialchars($this->pObj->pi_getLL('startAt')),
			'ARRIVAL_TO' => htmlspecialchars($this->pObj->pi_getLL('arrivalAt')),
			'PREFERENCES' => htmlspecialchars($this->pObj->pi_getLL('preference')),
			'MODE_TYPE' => htmlspecialchars($this->pObj->pi_getLL('preference.mode')),
			'CRITERIA' => htmlspecialchars($this->pObj->pi_getLL('preference.criteria')),
			'RESULT_HOURS_START' => htmlspecialchars($this->pObj->pi_getLL('result_hour_start')),
			'AND' => htmlspecialchars($this->pObj->pi_getLL('and')),
			'FIRST_HOUR' => '',
			'LAST_HOUR' => '',
			'START_HOUR' => '',
			'ARRIVAL_HOUR' => '',
			'PREVIOUS_JOURNEY' => htmlspecialchars($this->pObj->pi_getLL('results.previous')),
			'NEXT_JOURNEY' => htmlspecialchars($this->pObj->pi_getLL('results.next')),
			'SELECTED_0' => '',
			'SELECTED_1' => '',
			'ACTION_URL' => htmlspecialchars($this->pObj->pi_linkTP_keepPIvars_url()),
			'HIDDEN_FIELDS' => $this->pObj->getHiddenFields(),
		);
		$params = t3lib_div::_GET($this->pObj->prefixId);
		foreach ($params as $name => $value) {
			if (is_array($value))
				continue;
			if (in_array($name, array('isStartTime', 'date', 'hour', 'mode', 'criteria')))
				continue;
			$markers['HIDDEN_FIELDS'] .= '<input type="hidden" name="' . htmlspecialchars($this->pObj->prefixId) . '[' . htmlspecialchars($name) . ']" value="' . htmlspecialchars($value) . '" />';
		}
		
		$markers['SELECTED_' . $this->pObj->piVars['isStartTime']] = ' selected="selected"';
		
		$markers['DATE_SEL'] = $this->pObj->piVars['date'];
		$markers['HOUR_SEL'] = $this->pObj->piVars['hour'];

		$template = $this->pObj->replaceModes($template);
		$template = $this->pObj->replaceCriteria($template);

		if (!is_null($journeyPlan['JourneyResultList']->Get(0)->summary)) {
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
		
		$template = $this->pObj->cObj->substituteSubpart($template, '###RESULTS_LIST###', $this->renderResults($template, $journeyPlan['JourneyResultList']));
		
		$content = $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $content;
	}
	
	private function renderResults($template, tx_icslibnavitia_INodeList $results) {
		$linePicto = t3lib_div::makeInstance('tx_icslinepicto_getlines');
		$resultListTemplate = $this->pObj->cObj->getSubpart($template, '###RESULTS_LIST###');
		$resultListContent = '';
		foreach ($results->ToArray() as $journeyResult) {
			$markers = array();
			$markers['DETAILS_URL'] = $this->pObj->pi_linkTP_keepPIvars_url(
				array(
					'nbBefore' 	=> '0',
					'nbAfter' 	=> '0',
					'date' 		=> $journeyResult->summary->departure->format('d/m/Y'), 
					'hour' 		=> $journeyResult->summary->departure->format('H:i')
				)
			);
		
			if (!is_null($journeyResult->summary)) {
				$markers['START_HOUR'] = $journeyResult->summary->departure->format('H:i');
				$markers['ARRIVAL_HOUR'] = $journeyResult->summary->arrival->format('H:i');
			}
			foreach ($journeyResult->sections->ToArray() as $section) {
				$duration = '';
				
				if($journeyResult->summary->duration->day) {
					$duration .= $journeyResult->summary->duration->day . ' ' . $this->pObj->pi_getLL('day');
				}
				
				if($journeyResult->summary->duration->hour) {
					$duration .= $journeyResult->summary->duration->hour . ' ' . $this->pObj->pi_getLL('hour');
				}
				
				if($journeyResult->summary->duration->minute) {
					$duration .= $journeyResult->summary->duration->minute . ' ' . $this->pObj->pi_getLL('minute');
				}
				
				$markers['DURATION'] = $duration;
				$markers['PICTOS'] = $linePicto->getlinepicto($section->vehicleJourney->route->line->externalCode, 'Navitia');
			}
			
			$resultListContent .= $this->pObj->cObj->substituteMarkerArray($resultListTemplate, $markers, '###|###');
		}
		return $resultListContent;
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