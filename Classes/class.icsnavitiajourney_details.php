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
 
class tx_icsnavitiajourney_details {

	public function __construct($pObj) {
		$this->pObj = $pObj;
	}

	function getPlanJourneyDetails($journeyPlan, $params) {
		
		$templatePart = $this->pObj->templates['details'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_SEARCH_DETAILS###');
		
		$markers = array(
			'###PREFIXID###' => $this->pObj->prefixId,
			'###SEARCH###' => $this->pObj->pi_getLL('menu.search'),
			'###RESULTS###' => $this->pObj->pi_getLL('menu.results'),
			'###DETAILS###' => $this->pObj->pi_getLL('menu.details'),
			'###ACTION_URL###' => $this->pObj->pi_linkTP_keepPIvars_url(),
			'###DIRECTION_LABEL###' => $this->pObj->pi_getLL('direction'),
			'###FROM###' => $this->pObj->pi_getLL('from'),
			'###TO###' => $this->pObj->pi_getLL('to'),
			'###PREVIOUS_JOURNEY###' => $this->pObj->pi_getLL('results.previous'),
			'###NEXT_JOURNEY###' => $this->pObj->pi_getLL('results.next'),
			'###BACKWARD_JOURNEY###' => $this->pObj->pi_getLL('details.backward'),
		);
		
		$markers['###DATE_SEL###'] = $this->pObj->piVars['date'];
		$markers['###HOUR_SEL###'] = $this->pObj->piVars['hour'];

		$detailsTemplate = $this->pObj->cObj->getSubpart($template, '###CONNECTIONS_LIST###');
		$journeyResult = $journeyPlan['JourneyResultList']->Get(0);
		
		if(!is_null($journeyResult->summary)) {
			$markers['###START_HOUR###'] = $journeyResult->summary->departure->format('H:i');
		}
		
		$markers['###PREVIOUS_JOURNEY_LINK###'] = $this->pObj->pi_linkTP_keepPIvars_url(
			array(
				'date' 		=> $journeyResult->summary->call->before->dateTime->format('d/m/Y'), 
				'hour' 		=> $journeyResult->summary->call->before->dateTime->format('H:i') // renvoi 00:00 (voir pourquoi)
			)
		);
		
		$markers['###NEXT_JOURNEY_LINK###'] = $this->pObj->pi_linkTP_keepPIvars_url(
			array(
				'date' 		=> $journeyResult->summary->call->after->dateTime->format('d/m/Y'), 
				'hour' 		=> $journeyResult->summary->call->after->dateTime->format('H:i') // renvoi 00:00 (voir pourquoi)
			)
		);
		
		$linePicto = t3lib_div::makeInstance('tx_icslinepicto_getlines');
		foreach($journeyResult->sections->ToArray() as $section) {
		
			$markers['###START_HOUR###'] = $section->departure->dateTime->format('H:i');
			$markers['###STEP_JOURNEY_INFOS###'] = $section->departure->{lcfirst($section->departure->type)}->city->name . ' -  ' . $section->departure->{lcfirst($section->departure->type)}->name;
			$markers['###PICTO###'] = $linePicto->getlinepicto($section->vehicleJourney->route->line->externalCode, 'Navitia');

			if(empty($markers['###PICTO###']) && !is_null($section->vehicleJourney->route->line)) {
				$markers['###PICTO###'] = 'Ligne ' . $section->vehicleJourney->route->line->code;// temporaire pendant qu'on a pas les pictos dans la bdd.
			}
			
			$markers['###LINE_NAME###'] = $section->vehicleJourney->route->line->name;
			
				$duration = '';
				
				if($section->duration->day) {
					$duration .= $section->duration->day . ' ' . $this->pObj->pi_getLL('day');
				}
				
				if($section->durationhour) {
					$duration .= $section->duration->hour . ' ' . $this->pObj->pi_getLL('hour');
				}
				
				if($section->duration->minute) {
					$duration .= $section->duration->minute . ' ' . $this->pObj->pi_getLL('minute');
				}
				
				$markers['###STEP_DURATION###'] = $duration;
				$markers['###DIRECTION###'] = $section->vehicleJourney->route->line->forward->name;
				$markers['###STOP_START###'] = $section->departure->{lcfirst($section->departure->type)}->stopArea->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
				$markers['###STOP_ARRIVAL###'] = $section->arrival->{lcfirst($section->arrival->type)}->stopArea->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
			
			$detailsContent .= $this->pObj->cObj->substituteMarkerArray($detailsTemplate, $markers);
		}
		
		//foreach($journeyPlan['JourneyResultList']->ToArray() as $journeyResult) {
		
			
		
			/*foreach($journeyResult->sections->ToArray() as $section) {
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
			}*/

		$template = $this->pObj->cObj->substituteSubpart($template, '###CONNECTIONS_LIST###', $detailsContent);
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