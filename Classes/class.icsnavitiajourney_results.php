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
		
		//var_dump($params);
		//t3lib_div::devlog('Appel pi1 journey results', 'ics_navitia_journey', 0, $params);
		ini_set('display_errors', 1);
		
		$markers = array(
			'###PREFIXID###' => $this->pObj->prefixId,
			'###FROM###' => $this->pObj->pi_getLL('from'),
			'###TO###' => $this->pObj->pi_getLL('to'),
			'###SEARCH###' => $this->pObj->pi_getLL('menu.search'),
			'###RESULTS###' => $this->pObj->pi_getLL('menu.results'),
			'###DETAILS###' => $this->pObj->pi_getLL('menu.details'),
			'###STOP_START###' => $params['startName'],
			'###CITY_START###' => $params['startCity'],
			'###STOP_ARRIVAL###' => $params['arrivalName'],
			'###CITY_ARRIVAL###' => $params['arrivalCity'],
			'###START_TO###' => $this->pObj->pi_getLL('startAt'),
			'###ARRIVAL_TO###' => $this->pObj->pi_getLL('arrivalAt'),
			'###PREFERENCES###' => $this->pObj->pi_getLL('preference'),
			'###MODE_TYPE###' => $this->pObj->pi_getLL('preference.mode'),
			'###CRITERIA###' => $this->pObj->pi_getLL('preference.criteria'),
			'###RESULT_HOURS_START###' => $this->pObj->pi_getLL('result_hour_start'),
			'###AND###' => $this->pObj->pi_getLL('and'),
			'###FIRST_HOUR###' => '',
			'###LAST_HOUR###' => '',
			'###START_HOUR###' => '',
			'###ARRIVAL_HOUR###' => '',
			'###PREVIOUS_JOURNEY###' => $this->pObj->pi_getLL('results.previous'),
			'###NEXT_JOURNEY###' => $this->pObj->pi_getLL('results.next'),
			'###SELECTED_0###' => '',
			'###SELECTED_1###' => '',
			'###ACTION_URL###' => $this->pObj->pi_linkTP_keepPIvars_url(),
		);
		
		$markers['###SELECTED_' . $this->pObj->piVars['isStartTime'] . '###'] = 'selected="selected"';
		
		$markers['###DATE_SEL###'] = $this->pObj->piVars['date'];
		$markers['###HOUR_SEL###'] = $this->pObj->piVars['hour'];
		
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

		if(!is_null($journeyPlan['JourneyResultList']->Get(0)->summary)) {
			$markers['###FIRST_HOUR###'] = $journeyPlan['JourneyResultList']->Get(0)->summary->departure->format('H:i');
			$markers['###LAST_HOUR###'] = $journeyPlan['JourneyResultList']->Get(intval($journeyPlan['JourneyResultList']->Count()-1))->summary->departure->format('H:i');
			
			$markers['###PREVIOUS_JOURNEY_LINK###'] = $this->pObj->pi_linkTP_keepPIvars_url(
				array(
					'date' 		=> $journeyPlan['JourneyResultList']->Get(0)->summary->departure->format('d/m/Y'), 
					'hour' 		=> $journeyPlan['JourneyResultList']->Get(0)->summary->departure->format('H:i')
				)
			);
			
			$markers['###NEXT_JOURNEY_LINK###'] = $this->pObj->pi_linkTP_keepPIvars_url(
				array(
					'date' 		=> $journeyPlan['JourneyResultList']->Get($journeyPlan['JourneyResultList']->Count()-1)->summary->departure->format('d/m/Y'), 
					'hour' 		=> $journeyPlan['JourneyResultList']->Get($journeyPlan['JourneyResultList']->Count()-1)->summary->departure->format('H:i')
				)
			);
		}
		$linePicto = t3lib_div::makeInstance('tx_icslinepicto_getlines');
		$resultListTemplate = $this->pObj->cObj->getSubpart($template, '###RESULTS_LIST###');
		foreach($journeyPlan['JourneyResultList']->ToArray() as $journeyResult) {
			$markers['###DETAILS_URL###'] = $this->pObj->pi_linkTP_keepPIvars_url(
				array(
					'nbBefore' 	=> '0',
					'nbAfter' 	=> '0',
					'date' 		=> $journeyResult->summary->departure->format('d/m/Y'), 
					'hour' 		=> $journeyResult->summary->departure->format('H:i')
				)
			);
		
			if(!is_null($journeyResult->summary)) {
				$markers['###START_HOUR###'] = $journeyResult->summary->departure->format('H:i');
				$markers['###ARRIVAL_HOUR###'] = $journeyResult->summary->arrival->format('H:i');
			}
			foreach($journeyResult->sections->ToArray() as $section) {
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
				
				$markers['###DURATION###'] = $duration;
				$markers['###PICTOS###'] = $linePicto->getlinepicto($section->vehicleJourney->route->line->externalCode, 'Navitia');
			}
			
			$resultListContent .= $this->pObj->cObj->substituteMarkerArray($resultListTemplate, $markers);
		}
		
		//var_dump($this->pObj->dataProvider->getLastParams());
		
		//t3lib_div::devlog('Appel pi1 journey results', 'ics_navitia_journey', 0, $params);
		//var_dump(html_entity_decode('http://rennes.prod.navitia.com/cgi-bin/gwnavitia.dll/API?Action=PlanJourney&amp;Departure=StopArea%7C949%7CR%E9publique%7CRennes%7C%7C%7C301043.51%7C2353261.36%7C2268%210%2114%3B2270%210%2114%3B2255%210%2114%3B2258%210%2114%3B2257%210%2114%3B2256%210%2114%3B2259%210%2114%3B2261%210%2114%3B2269%210%2114%3B2264%210%2114%3B2262%210%2114%3B2267%210%2114%3B2265%210%2114%3B2266%210%2114%3B2260%210%2114%3B2263%210%2114&amp;Arrival=StopArea%7C810%7CGares%7CRennes%7C%7C%7C301546.45%7C2352622.69%7C1893%210%2114%3B1892%210%2114%3B1891%210%2114&amp;Sens=1&amp;Time=11%7C52&amp;Date=2011%7C09%7C02&amp;Criteria=&amp;NbBefore=1&amp;NbAfter=1&amp;Interface=1_16&amp;login=StarWeb&amp;RequestUrl=http%3A%2F%2Fkeomobile.ic-s.org%2Findex.php%3Fid%3D108%2526tx_icsnavitiajourney_pi1%5BstartName%5D%3Drepublique%2526tx_icsnavitiajourney_pi1%5BstartCity%5D%3Drennes%2526tx_icsnavitiajourney_pi1%5BentryPointStart%5D%3D0%2526tx_icsnavitiajourney_pi1%5BarrivalName%5D%3Dgares%2526tx_icsnavitiajourney_pi1%5BarrivalCity%5D%3Drennes%2526tx_icsnavitiajourney_pi1%5BentryPointArrival%5D%3D3%2526tx_icsnavitiajourney_pi1%5BisStartTime%5D%3D1%2526tx_icsnavitiajourney_pi1%5Bdate%5D%3D02%252F09%252F2011%2526tx_icsnavitiajourney_pi1%5Bhour%5D%3D11%253A52%2526tx_icsnavitiajourney_pi1%5Bmode%5D%3DBus%2526tx_icsnavitiajourney_pi1%5Bmode%5D%3Dm%25E9tro%2526tx_icsnavitiajourney_pi1%5Bcriteria%5D%3D1%2526tx_icsnavitiajourney_pi1%5BsearchSubmit%5D%3DValider%2526navitia_debug%3D1'));
		
		$template = $this->pObj->cObj->substituteSubpart($template, '###RESULTS_LIST###', $resultListContent);
		$template = $this->pObj->cObj->substituteSubpart($template, '###CRITERIA_LIST###', $criteriaListContent);
		$template = $this->pObj->cObj->substituteSubpart($template, '###MODE_TYPE_LIST###', $modeTypeListContent);
		$content = $this->pObj->cObj->substituteMarkerArray($template, $markers);
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