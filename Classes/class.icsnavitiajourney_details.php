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

	var $aPicto = array(
		'bus' => 'bus.png',
		'métro' => 'metro.png',
		'walk' => 'walk.png',
	);

	public function __construct($pObj) {
		$this->pObj = $pObj;
	}
	
	private function getSettings() {
		//var_dump($this->pObj->conf);
	}

	function getPlanJourneyDetails($journeyPlan, $params) {
		$this->getSettings();
		
		$templatePart = $this->pObj->templates['details'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_SEARCH_DETAILS###');
		
		$markers = array(
			'PREFIXID' => $this->pObj->prefixId,
			'SEARCH' => $this->pObj->pi_getLL('menu.search'),
			'RESULTS' => $this->pObj->pi_getLL('menu.results'),
			'DETAILS' => $this->pObj->pi_getLL('menu.details'),
			'ACTION_URL' => $this->pObj->pi_linkTP_keepPIvars_url(),
			'FROM' => $this->pObj->pi_getLL('from'),
			'TO' => $this->pObj->pi_getLL('to'),
			'PREVIOUS_JOURNEY' => $this->pObj->pi_getLL('results.previous'),
			'NEXT_JOURNEY' => $this->pObj->pi_getLL('results.next'),
			'BACKWARD_JOURNEY' => $this->pObj->pi_getLL('details.backward'),
			'FROM_RECAP' => $this->pObj->pi_getLL('from'),
			'TO_RECAP' => $this->pObj->pi_getLL('to'),
			'STOP_START_RECAP' => htmlspecialchars($params['startName']),
			'CITY_START_RECAP' => htmlspecialchars($params['startCity']),
			'STOP_ARRIVAL_RECAP' => htmlspecialchars($params['arrivalName']),
			'CITY_ARRIVAL_RECAP' => htmlspecialchars($params['arrivalCity']),
			'BACKWARD_JOURNEY_LINK' => $this->pObj->pi_linkTP_keepPIvars_url(
				array(
					'startName' 			=> $this->pObj->piVars['arrivalName'],
					'arrivalName' 			=> $this->pObj->piVars['startName'],
					'startCity' 			=> $this->pObj->piVars['arrivalCity'],
					'arrivalCity' 			=> $this->pObj->piVars['startCity'],
					'entryPointStart' 		=> $this->pObj->piVars['entryPointArrival'],
					'entryPointArrival' 	=> $this->pObj->piVars['entryPointStart'],
				)
			),
			'PLAN' => $this->pObj->pi_getLL('details.localize.stoparea')
		);
		
		//var_dump(intval($this->pObj->piVars['hour']));
		
		$markers['DATE_SEL'] = $this->pObj->piVars['date'];
		$markers['HOUR_SEL'] = $this->pObj->piVars['hour'];

		$detailsTemplate = $this->pObj->cObj->getSubpart($template, '###CONNECTIONS_LIST###');
		$journeyResult = $journeyPlan['JourneyResultList']->Get(0);
		
		if(!is_null($journeyResult->summary)) {
			$markers['START_HOUR'] = $journeyResult->summary->departure->format('H:i');
		}
		
		$markers['PREVIOUS_JOURNEY_LINK'] = $this->pObj->pi_linkTP_keepPIvars_url(
			array(
				'date' 		=> $journeyResult->summary->call->before->dateTime->format('d/m/Y'), 
				'hour' 		=> $journeyResult->summary->call->before->dateTime->format('H:i') // renvoi 00:00 (voir pourquoi)
			)
		);
		
		$markers['NEXT_JOURNEY_LINK'] = $this->pObj->pi_linkTP_keepPIvars_url(
			array(
				'date' 		=> $journeyResult->summary->call->after->dateTime->format('d/m/Y'), 
				'hour' 		=> $journeyResult->summary->call->after->dateTime->format('H:i') // renvoi 00:00 (voir pourquoi)
			)
		);
		
		$linePicto = t3lib_div::makeInstance('tx_icslinepicto_getlines');
		
		$indexS = 0;
		foreach($journeyResult->sections->ToArray() as $section) {
			$vehicleJourneyConnection = '';
			$stopPointConnection = '';
			$linkConnection = '';
			$addressConnection = '';
			$siteConnection = '';
			
			$markers['DIRECTION'] = '';
			$markers['PICTO_SEPARATOR'] = '';
			$markers['DIRECTION_LABEL'] = '';
			$markers['START_HOUR'] = $section->departure->dateTime->format('H:i');
			$markers['ARRIVAL_HOUR'] = $section->arrival->dateTime->format('H:i');
			//$markers['STEP_JOURNEY_INFOS'] = $section->departure->{lcfirst($section->departure->type)}->city->name . ' -  ' . $section->departure->{lcfirst($section->departure->type)}->name;
			$markers['PICTO'] = $linePicto->getlinepicto($section->vehicleJourney->route->line->externalCode, 'Navitia');
			
			$confImg = array();
			switch($section->type) {
				case 'VehicleJourneyConnection' :
					$confImg['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/' . $this->aPicto[strtolower($section->vehicleJourney->route->line->modeType->externalCode)];
					break;
				default :
					if(!$index || ($index && $aPicto[$index-1] != 'walk')) {
						$confImg['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/' . $this->aPicto['walk'];
					}
				break;
			}
			
			if(!empty($confImg['file'])) {
				$markers['PICTO_TYPE'] = $this->pObj->cObj->IMAGE($confImg);
			}
			
			/*if(!$indexS) {
				$markers['SECTION_INFO'] = $this->pObj->pi_getLL('details.departure');
			}
			elseif($indexS == $journeyResult->sections->Count()-1) {
				$markers['SECTION_INFO'] = $this->pObj->pi_getLL('details.arrival');
			}
			else {
				$markers['SECTION_INFO'] = '';
			}*/
			
			if(empty($markers['PICTO']) && !is_null($section->vehicleJourney->route->line)) {
				$markers['PICTO'] = 'Ligne ' . $section->vehicleJourney->route->line->code;// temporaire pendant qu'on a pas les pictos dans la bdd.
			}
			
			$markers['LINE_NAME'] = $section->vehicleJourney->route->line->name;
			
			$duration = '';
			$viewPlanTemplate = $this->pObj->cObj->getSubpart($detailsTemplate, '###VIEW_PLAN###');
			switch($section->type) {
				case 'WalkConnection': 
					$viewPlan = $this->pObj->cObj->substituteMarkerArray($viewPlanTemplate, $markers, '###|###');
					//$sectionType = 'Liaison à pied sur l\'ensemble de l\'itinéraire';
					break;
				case 'VehicleJourneyConnection':
					if($section->vehicleJourney->route->line->forward->name) {
						$markers['FROM'] = $this->pObj->pi_getLL('from');
						$markers['TO'] = $this->pObj->pi_getLL('to');
						$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->stopArea->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
						$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->stopArea->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
						
						$markers['DIRECTION'] = $section->vehicleJourney->route->line->forward->name;
						$markers['DIRECTION_LABEL'] = $this->pObj->pi_getLL('direction');
						$vehicleJourneyConnectionTemplate = $this->pObj->cObj->getSubpart($detailsTemplate, '###VEHICLE_JOURNEY_CONNECTION###');
						$vehicleJourneyConnection = $this->pObj->cObj->substituteMarkerArray($vehicleJourneyConnectionTemplate, $markers, '###|###');
						$confFleche = array();
						$confFleche['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/fleche.png';
						$markers['PICTO_SEPARATOR'] = $this->pObj->cObj->IMAGE($confFleche);
						$viewPlan = '';
					}
					break;
					
				case 'SiteConnection':
					$markers['FROM'] = $this->pObj->pi_getLL('from');
					$markers['TO'] = $this->pObj->pi_getLL('details.to.site.stoppoint');
					if($section->departure->type == 'Site') {
						$markers['FROM'] = $this->pObj->pi_getLL('details.from.site');
					}
					elseif($section->arrival->type == 'Site') {
						$markers['TO'] = $this->pObj->pi_getLL('details.to.site');
					}

					$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
					$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
					
					$markers['DIRECTION'] = $section->vehicleJourney->route->line->forward->name;
					$markers['DIRECTION_LABEL'] = $this->pObj->pi_getLL('direction');
					
					$siteConnectionTemplate = $this->pObj->cObj->getSubpart($detailsTemplate, '###SITE_CONNECTION###');
					$siteConnection = $this->pObj->cObj->substituteMarkerArray($siteConnectionTemplate, $markers, '###|###');
					$viewPlan = $this->pObj->cObj->substituteMarkerArray($viewPlanTemplate, $markers, '###|###');
					break;
				case 'AddressConnection':
					$markers['FROM'] = $this->pObj->pi_getLL('from');
					$markers['TO'] = $this->pObj->pi_getLL('details.to.address.stoppoint');
					if($section->departure->type == 'Address') {
						$markers['FROM'] = $this->pObj->pi_getLL('details.from.address');
						//$markers['ADDRESS_CONNECTION_INFO'] = $this->pObj->pi_getLL('details.addressConnection.from') . ' '  . $section->departure->address->city->name . ' - ' . $section->departure->address->name;
					}
					else {
						$markers['TO'] = $this->pObj->pi_getLL('details.to.address');
						//$markers['ADDRESS_CONNECTION_INFO'] = $this->pObj->pi_getLL('details.addressConnection.to') . ' '  . $section->arrival->address->city->name . ' - ' . $section->arrival->address->name;
					}
					
					$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
					$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
					
					$addressConnectionTemplate = $this->pObj->cObj->getSubpart($detailsTemplate, '###ADDRESS_CONNECTION###');
					$addressConnection = $this->pObj->cObj->substituteMarkerArray($addressConnectionTemplate, $markers, '###|###');
					
					$viewPlan = $this->pObj->cObj->substituteMarkerArray($viewPlanTemplate, $markers, '###|###');
					break;
				case 'ProlongationConnection': 
					//$sectionType = 'Prolongement de service ou Haut le pied : temps d\'attente dans le bus (généralement sur un terminus en boucle, sur une pour réguler les horaires)';
					break;
				case 'ODTConnection': 
					//$sectionType = 'Liaison en transport à la demande. Si les horaires sont estimés, lire la propriété EstimatedTime';
					break;
				case 'StopPointConnection': 
					if(is_array($aDuration[$indexS-1])) { // fusion des sections linkConnection et StopPointConnection. Les durées se cumulent.
						$markers['SECTION_INFO'] = $this->pObj->pi_getLL('details.linkConnection');
					}
					else {
						//$markers['SECTION_INFO'] = 'Changement';
						$markers['DIRECTION'] = $this->pObj->pi_getLL('details.correspondence');
						//$markers['STEP_JOURNEY_INFOS'] = '';
					}
					
					$markers['FROM'] = $this->pObj->pi_getLL('from');
					$markers['TO'] = $this->pObj->pi_getLL('to');
					
					$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
					$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
					
					$markers['CORRESPONDENCE'] = $this->pObj->pi_getLL('details.correspondence');

					$stopPointConnectionTemplate = $this->pObj->cObj->getSubpart($detailsTemplate, '###STOP_POINT_CONNECTION###');
					$stopPointConnection = $this->pObj->cObj->substituteMarkerArray($stopPointConnectionTemplate, $markers, '###|###');
					$viewPlan = $this->pObj->cObj->substituteMarkerArray($viewPlanTemplate, $markers, '###|###');
					break;
				case 'LinkConnection' : 
					//$aLinkConnection[] = $indexS;
					//$sectionType = 'Rejoindre à pied l\'arrêt.';
					//$markers['LINK_CONNECTION_INFO'] = $this->pObj->pi_getLL('details.linkConnection') . ' ' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' -  ' . $section->departure->{lcfirst($section->departure->type)}->name;
					//$markers['SECTION_INFO'] = '';
					//$markers['STEP_JOURNEY_INFOS'] = '';
					//$linkConnectionTemplate = $this->pObj->cObj->getSubpart($detailsTemplate, '###LINK_CONNECTION###');
					//$linkConnection = $this->pObj->cObj->substituteMarkerArray($linkConnectionTemplate, $markers, '###|###');
					break;
				case 'Undefined': 
					//$sectionType = 'Liaison à pied entre un point géocodé sans nature et un point d\'arrêt';
					break;
			}

			if($section->duration->day) {
				$duration .= $section->duration->day + intval($aDuration[$indexS-1]['day']) . ' ' . $this->pObj->pi_getLL('day');
			}
			
			if($section->duration->hour) {
				$duration .= $section->duration->hour + intval($aDuration[$indexS-1]['hour']) . ' ' . $this->pObj->pi_getLL('hour');
			}
			
			if($section->duration->minute) {
				$duration .= $section->duration->minute + intval($aDuration[$indexS-1]['minute']) . ' ' . $this->pObj->pi_getLL('minute');
			}
			
			if($section->type == 'LinkConnection') {
				$aDuration[$indexS]['day'] = $section->duration->day;
				$aDuration[$indexS]['hour'] = $section->duration->hour;
				$aDuration[$indexS]['minute'] = $section->duration->minute;
				$indexS++;
				continue;
			}
			
			$markers['STEP_DURATION'] = $duration;
			
			$detailsTemplateSection = $this->pObj->cObj->substituteSubpart($detailsTemplate, '###VEHICLE_JOURNEY_CONNECTION###', $vehicleJourneyConnection);
			$detailsTemplateSection = $this->pObj->cObj->substituteSubpart($detailsTemplateSection, '###STOP_POINT_CONNECTION###', $stopPointConnection);
			$detailsTemplateSection = $this->pObj->cObj->substituteSubpart($detailsTemplateSection, '###VIEW_PLAN###', $viewPlan);
			$detailsTemplateSection = $this->pObj->cObj->substituteSubpart($detailsTemplateSection, '###SITE_CONNECTION###', $siteConnection);
			$detailsTemplateSection = $this->pObj->cObj->substituteSubpart($detailsTemplateSection, '###LINK_CONNECTION###', $linkConnection);
			$detailsTemplateSection = $this->pObj->cObj->substituteSubpart($detailsTemplateSection, '###ADDRESS_CONNECTION###', $addressConnection);
			
			$detailsContent .= $this->pObj->cObj->substituteMarkerArray($detailsTemplateSection, $markers, '###|###');
			$indexS++;
		}

		$template = $this->pObj->cObj->substituteSubpart($template, '###CONNECTIONS_LIST###', $detailsContent);
		
		$content = $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
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