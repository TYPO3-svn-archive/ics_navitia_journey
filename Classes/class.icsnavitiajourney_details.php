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
	private $pObj;

	/*var $aPicto = array(
		'bus' => 'bus.png',
		'métro' => 'metro.png',
		'walk' => 'walk.png',
	);*/

	public function __construct($pObj) {
		$this->pObj = $pObj;
	}
	
	private function getSettings() {
		//var_dump($this->pObj->conf);
	}

	public function getPlanJourneyDetails($journeyPlan, $params) {
		$this->getSettings();
		
		$templatePart = $this->pObj->templates['details'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_SEARCH_DETAILS###');
		
		$markers = array(
			'PREFIXID' => $this->pObj->prefixId,
			'SEARCH' => $this->pObj->pi_getLL('menu_search'),
			'SEARCH_LINK' => $this->pObj->pi_getPageLink($GLOBALS['TSFE']->id),
			'RESULTS' => $this->pObj->pi_getLL('menu_results'),
			'DETAILS' => $this->pObj->pi_getLL('menu_details'),
			'ACTION_URL' => $this->pObj->pi_linkTP_keepPIvars_url(),
			// 'FROM' => $this->pObj->pi_getLL('from'),
			// 'TO' => $this->pObj->pi_getLL('to'),
			'PREVIOUS_JOURNEY' => $this->pObj->pi_getLL('results_previous'),
			'NEXT_JOURNEY' => $this->pObj->pi_getLL('results_next'),
			'BACKWARD_JOURNEY' => $this->pObj->pi_getLL('details_backward'),
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
			'FROM_RECAP' => $this->pObj->pi_getLL('from'),
			'TO_RECAP' => $this->pObj->pi_getLL('to'),
			'STOP_START_RECAP' => htmlspecialchars($params['startName']),
			'CITY_START_RECAP' => htmlspecialchars($params['startCity']),
			'STOP_ARRIVAL_RECAP' => htmlspecialchars($params['arrivalName']),
			'CITY_ARRIVAL_RECAP' => htmlspecialchars($params['arrivalCity']),
		);
		
		$markers['DATE_SEL'] = $this->pObj->piVars['date'];
		$markers['HOUR_SEL'] = $this->pObj->piVars['hour'];

		$detailsTemplate = $this->pObj->cObj->getSubpart($template, '###CONNECTIONS_LIST###');
		$journeyResult = $journeyPlan['JourneyResultList']->Get(0);
		
		if (!is_null($journeyResult->summary)) {
			$markers['START_HOUR'] = $journeyResult->summary->departure->format('H:i');
		}
		
		$markers['PREVIOUS_JOURNEY_LINK'] = $this->pObj->pi_linkTP_keepPIvars_url(
			array(
				'date' => $journeyResult->summary->call->before->dateTime->format('d/m/Y'), 
				'hour' => $journeyResult->summary->call->before->dateTime->format('H:i'),
			)
		);
		
		$markers['NEXT_JOURNEY_LINK'] = $this->pObj->pi_linkTP_keepPIvars_url(
			array(
				'date' => $journeyResult->summary->call->after->dateTime->format('d/m/Y'), 
				'hour' => $journeyResult->summary->call->after->dateTime->format('H:i'),
			)
		);
		
		$this->linePicto = t3lib_div::makeInstance('tx_icslinepicto_getlines');
		$durations = array();
		$detailsContent = '';
		
		$index = 0;
		foreach ($journeyResult->sections->ToArray() as $section) {
			$detailsContent .= $this->renderSection($detailsTemplate, $section, $durations, $index++);
		}

		$template = $this->pObj->cObj->substituteSubpart($template, '###CONNECTIONS_LIST###', $detailsContent);
		
		$content = $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $content;
	}
	
	private function renderSection($template, tx_icslibnavitia_Section $section, array & $durations, $index) {
		if ($section->type == 'LinkConnection') {
			$durations[$index]['day'] = $section->duration->day;
			$durations[$index]['hour'] = $section->duration->hour;
			$durations[$index]['minute'] = $section->duration->minute;
			return '';
		}

		$markers = array();
		$markers['DIRECTION'] = '';
		$markers['PICTO_SEPARATOR'] = '';
		$markers['DIRECTION_LABEL'] = '';
		$markers['START_HOUR'] = $section->departure->dateTime->format('H:i');
		$markers['ARRIVAL_HOUR'] = $section->arrival->dateTime->format('H:i');
		//$markers['STEP_JOURNEY_INFOS'] = $section->departure->{lcfirst($section->departure->type)}->city->name . ' -  ' . $section->departure->{lcfirst($section->departure->type)}->name;
		$markers['PICTO'] = $this->linePicto->getlinepicto($section->vehicleJourney->route->line->externalCode, 'Navitia');
		$markers['MAP'] = '';
		
		$confImg = array();
		/*switch ($section->type) {
			case 'VehicleJourneyConnection' :
				$confImg['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/' . $this->aPicto[strtolower($section->vehicleJourney->route->line->modeType->externalCode)];
				break;
			default:
				if (!$index || ($index && $aPicto[$index-1] != 'walk')) {
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
		
		if (!empty($confImg['file'])) {
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
		
		if (empty($markers['PICTO']) && !is_null($section->vehicleJourney->route->line)) {
			$markers['PICTO'] = 'Ligne ' . $section->vehicleJourney->route->line->code;// temporaire pendant qu'on a pas les pictos dans la bdd.
		}
		
		$markers['LINE_NAME'] = $section->vehicleJourney->route->line->name;
		
		$duration = '';
		
		$sectionMethod = 'render' . $section->type;
		$connection = '';
		if (method_exists($this, $sectionMethod)) {
			$connection = $this->$sectionMethod($section, $markers);
		}

		if ($section->duration->day) {
			$duration .= $section->duration->day + intval($durations[$index - 1]['day']) . ' ' . $this->pObj->pi_getLL('day');
		}
		
		if ($section->duration->hour) {
			$duration .= $section->duration->hour + intval($durations[$index - 1]['hour']) . ' ' . $this->pObj->pi_getLL('hour');
		}
		
		if ($section->duration->minute) {
			$duration .= $section->duration->minute + intval($durations[$index - 1]['minute']) . ' ' . $this->pObj->pi_getLL('minute');
		}
		
		$markers['STEP_DURATION'] = $duration;
		
		$template = $this->pObj->cObj->substituteMarker($template, '###CONNECTION###', $connection);
		return $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
	}

	private function renderWalkConnection(tx_icslibnavitia_Section $section, array & $markers) {
		// $viewPlan = $this->pObj->cObj->substituteMarkerArray($viewPlanTemplate, $markers, '###|###');
		// $sectionType = 'Liaison à pied sur l\'ensemble de l\'itinéraire';
		return '';
	}
	
	private function renderVehicleJourneyConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = '';
		if ($section->vehicleJourney->route->line->forward->name) {
			$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_VEHICLE_JOURNEY_CONNECTION###');
			$markers['FROM'] = $this->pObj->pi_getLL('from');
			$markers['TO'] = $this->pObj->pi_getLL('to');
			$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->stopArea->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
			$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->stopArea->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
			
			$markers['DIRECTION'] = $section->vehicleJourney->route->line->forward->name;
			$markers['DIRECTION_LABEL'] = $this->pObj->pi_getLL('direction');
			$confFleche = array();
			$confFleche['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/fleche.png';
			$markers['PICTO_SEPARATOR'] = $this->pObj->cObj->IMAGE($confFleche);
		}
		return $content;
	}
	
	private function renderSiteConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_SITE_CONNECTION###');
		
		$markers['FROM'] = $this->pObj->pi_getLL('from');
		$markers['TO'] = $this->pObj->pi_getLL('details_to_site_stoppoint');
		if ($section->departure->type == 'Site') {
			$markers['FROM'] = $this->pObj->pi_getLL('details_from_site');
		}
		elseif ($section->arrival->type == 'Site') {
			$markers['TO'] = $this->pObj->pi_getLL('details_to_site');
		}

		$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
		$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
		
		$markers['DIRECTION'] = $section->vehicleJourney->route->line->forward->name;
		$markers['DIRECTION_LABEL'] = $this->pObj->pi_getLL('direction');
		
		$coords = $section->arrival->{lcfirst($section->arrival->type)}->coord;
		if ($coords)
			$markers['MAP'] = $this->renderMap($coords);
		return $content;
	}
	
	private function renderAddressConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_ADDRESS_CONNECTION###');
		
		$markers['FROM'] = $this->pObj->pi_getLL('from');
		$markers['TO'] = $this->pObj->pi_getLL('details_to_address_stoppoint');
		if ($section->departure->type == 'Address') {
			$markers['FROM'] = $this->pObj->pi_getLL('details_from_address');
			//$markers['ADDRESS_CONNECTION_INFO'] = $this->pObj->pi_getLL('details.addressConnection.from') . ' '  . $section->departure->address->city->name . ' - ' . $section->departure->address->name;
		}
		else {
			$markers['TO'] = $this->pObj->pi_getLL('details_to_address');
			//$markers['ADDRESS_CONNECTION_INFO'] = $this->pObj->pi_getLL('details.addressConnection.to') . ' '  . $section->arrival->address->city->name . ' - ' . $section->arrival->address->name;
		}
		
		$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
		$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
		
		$coords = $section->arrival->{lcfirst($section->arrival->type)}->coord;
		if ($coords)
			$markers['MAP'] = $this->renderMap($coords);
		return $content;
	}
	
	private function renderProlongationConnection(tx_icslibnavitia_Section $section, array & $markers) {
		//$sectionType = 'Prolongement de service ou Haut le pied : temps d\'attente dans le bus (généralement sur un terminus en boucle, sur une pour réguler les horaires)';
		return '';
	}
	
	private function renderODTConnection(tx_icslibnavitia_Section $section, array & $markers) {
		//$sectionType = 'Liaison en transport à la demande. Si les horaires sont estimés, lire la propriété EstimatedTime';
		return '';
	}
	
	private function renderStopPointConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_STOP_POINT_CONNECTION###');
		
		if (is_array($aDuration[$indexS-1])) { // fusion des sections linkConnection et StopPointConnection. Les durées se cumulent.
			$markers['SECTION_INFO'] = $this->pObj->pi_getLL('details_linkConnection');
		}
		else {
			//$markers['SECTION_INFO'] = 'Changement';
			$markers['DIRECTION'] = $this->pObj->pi_getLL('details_correspondence');
			//$markers['STEP_JOURNEY_INFOS'] = '';
		}
		
		$markers['FROM'] = $this->pObj->pi_getLL('from');
		$markers['TO'] = $this->pObj->pi_getLL('to');
		
		$markers['STOP_START'] = $section->departure->{lcfirst($section->departure->type)}->name .  ' (' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' ) ';
		$markers['STOP_ARRIVAL'] = $section->arrival->{lcfirst($section->arrival->type)}->name .  ' (' . $section->arrival->{lcfirst($section->arrival->type)}->city->name . ') ';
		
		$markers['CORRESPONDENCE'] = $this->pObj->pi_getLL('details_correspondence');
		
		$coords = $section->arrival->{lcfirst($section->arrival->type)}->coord;
		if ($coords)
			$markers['MAP'] = $this->renderMap($coords);
		return $content;
	}
	
	private function renderLinkConnection(tx_icslibnavitia_Section $section, array & $markers) {
		//$aLinkConnection[] = $indexS;
		//$sectionType = 'Rejoindre à pied l\'arrêt.';
		//$markers['LINK_CONNECTION_INFO'] = $this->pObj->pi_getLL('details.linkConnection') . ' ' . $section->departure->{lcfirst($section->departure->type)}->city->name . ' -  ' . $section->departure->{lcfirst($section->departure->type)}->name;
		//$markers['SECTION_INFO'] = '';
		//$markers['STEP_JOURNEY_INFOS'] = '';
		//$linkConnectionTemplate = $this->pObj->cObj->getSubpart($detailsTemplate, '###LINK_CONNECTION###');
		//$linkConnection = $this->pObj->cObj->substituteMarkerArray($linkConnectionTemplate, $markers, '###|###');
		return '';
	}
	
	private function renderUndefined(tx_icslibnavitia_Section $section, array & $markers) {
		//$sectionType = 'Liaison à pied entre un point géocodé sans nature et un point d\'arrêt';
		return '';
	}
	
	private function renderMap(tx_icslibnavitia_Coord $coords) {
		$template = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_VIEW_MAP###');
		$baseConf = $this->pObj->conf['details.']['map.'];
		$markers = array(
			'TITLE' => htmlspecialchars($this->pObj->pi_getLL('details_localize_stoparea')),
			'STATIC_MAP' => 'http://maps.googleapis.com/maps/api/staticmap?' . t3lib_div::implodeArrayForUrl( // TODO: Use request scheme.
				'',
				array(
					'center' => $coords->lat . ',' . $coords->lng,
					'zoom' => $baseConf['zoom'],
					'size' => $baseConf['size'],
					'format' => $baseConf['format'],
					'maptype' => $baseConf['maptype'],
					'markers' => $coords->lat . ',' . $coords->lng,
					'sensor' => 'false',
				)
			),
		);
		return $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
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