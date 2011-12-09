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
	private $dataProvider;
	private $previousDeparture;
	private $previousDuration;

	public function __construct($pObj) {
		$this->pObj = $pObj;
	}

	public function getPlanJourneyDetails(tx_icslibnavitia_APIService $dataProvider, $journeyPlan, $params) {
		$this->dataProvider = $dataProvider;
		$templatePart = $this->pObj->templates['details'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_SEARCH_DETAILS###');
		
		if(t3lib_extMgm::isLoaded('ics_header_dyn')) {
			$headerDyn = t3lib_div::makeInstance('tx_icsheaderdyn_pi1');
			$this->dataTheme = $headerDyn->getPageHeaderColor();
		}
		else {
			$this->dataTheme = 'a';
		}
		
		$markers = array(
			'PREFIXID' => $this->pObj->prefixId,
			'SEARCH' => $this->pObj->pi_getLL('menu_search'),
			'SEARCH_LINK' => $this->pObj->pi_getPageLink($GLOBALS['TSFE']->id),
			'DATA_THEME' => $this->dataTheme,
			'RESULTS' => $this->pObj->pi_getLL('menu_results'),
			'DETAILS' => $this->pObj->pi_getLL('menu_details'),
			'ACTION_URL' => $this->pObj->pi_linkTP_keepPIvars_url(),
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
		if ($this->previousSection) {
			$previousSection = $this->previousSection;
			$this->previousDuration = null;
			$this->previousDeparture = null;
			$this->previousSection = null;
			$previousSection->type = 'ForcedLinkConnection';
			$detailsContent .= $this->renderSection($detailsTemplate, $previousSection, $durations, $index - 1);
			$previousSection->type = 'LinkConnection';
		}
		
		if ($journeyResult->sections->Get(0)->type == 'VehicleJourneyConnection') {
			$detailsContent = $this->renderDepartureMap($this->getCoords($journeyResult->sections->Get(0)->departure)) . $detailsContent;
		}
		if ($journeyResult->sections->Get($journeyResult->sections->Count() - 1)->type == 'VehicleJourneyConnection') {
			$detailsContent .= $this->renderArrivalMap($this->getCoords($journeyResult->sections->Get($journeyResult->sections->Count() - 1)->arrival));
		}

		$template = $this->pObj->cObj->substituteSubpart($template, '###CONNECTIONS_LIST###', $detailsContent);
		
		$content = $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $content;
	}
	
	private function renderSection($template, tx_icslibnavitia_Section $section, array & $durations, $index) {
		if ($section->type == 'LinkConnection') {
			$durations[$index] = $section->duration;
			$this->previousDuration = $durations[$index];
			$this->previousDeparture = $section->departure;
			$this->previousSection = $section;
			return '';
		}
		$content = '';
		if ($this->previousSection && ($section->type != 'StopPointConnection')) {
			$previousSection = $this->previousSection;
			$this->previousDuration = null;
			$this->previousDeparture = null;
			$this->previousSection = null;
			$previousSection->type = 'ForcedLinkConnection';
			$content .= $this->renderSection($template, $previousSection, $durations, $index - 1);
			$previousSection->type = 'LinkConnection';
		}

		$markers = array();
		$markers['DIRECTION'] = '';
		$markers['PICTO_SEPARATOR'] = '';
		$markers['DIRECTION_LABEL'] = '';
		$markers['START_HOUR'] = $section->departure->dateTime->format('H:i');
		$markers['ARRIVAL_HOUR'] = $section->arrival->dateTime->format('H:i');
		$markers['PICTO'] = $this->linePicto->getlinepicto($section->vehicleJourney->route->line->externalCode, 'Navitia');
		$markers['MAP'] = '';
		$markers['STEP_DURATION'] = htmlspecialchars($this->renderDuration($section->duration->totalSeconds + (($this->previousDuration) ? ($this->previousDuration->totalSeconds) : 0)));
		
		$confImg = array();
		$confImg['file'] = tx_icsnavitiajourney_results::getIcon($this->pObj->conf['icons.'], $section, $section->type);
		
		if (!empty($confImg['file'])) {
			$markers['PICTO_TYPE'] = $this->pObj->cObj->IMAGE($confImg);
		}
		else {
			$markers['PICTO_TYPE'] = $section->type;
		}
		
		if (empty($markers['PICTO']) && !is_null($section->vehicleJourney->route->line)) {
			$markers['PICTO'] = 'Ligne ' . $section->vehicleJourney->route->line->code;// temporaire pendant qu'on a pas les pictos dans la bdd.
		}
		
		$markers['LINE_NAME'] = $section->vehicleJourney->route->line->name;
		
		$duration = '';
		
		$sectionMethod = 'render' . $section->type;
		$connection = '';
		if (method_exists($this, $sectionMethod)) {
			$connection = $this->$sectionMethod($section, $markers);
			$this->previousDuration = null;
			$this->previousDeparture = null;
			$this->previousSection = null;
		}
		
		$template = $this->pObj->cObj->substituteMarker($template, '###CONNECTION###', $connection);
		$content .= $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $content;
	}
	
	private function renderDuration($seconds) {
		$minutes = (int)floor($seconds / 60) % 60;
		$hours = (int)floor($seconds / 3600) % 24;
		$days = (int)floor($seconds / 86400);
		$duration = array();
		if ($days) {
			$duration[] = $days . ' ' . $this->pObj->pi_getLL('day');
		}
		
		if ($hours) {
			$duration[] = $hours . ' ' . $this->pObj->pi_getLL('hour');
		}
		
		if ($minutes) {
			$duration[] = $minutes . ' ' . $this->pObj->pi_getLL('minute');
		}
		return implode(' ', $duration);
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
			$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from'));
			$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to'));
			$markers['START_NAME'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->stopArea->name);
			$markers['ARRIVAL_NAME'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->stopArea->name);
			$markers['START_CITY'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->city->name);
			$markers['ARRIVAL_CITY'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->city->name);
			
			$markers['DIRECTION'] = htmlspecialchars($section->vehicleJourney->route->line->forward->name);
			$markers['DIRECTION_LABEL'] = htmlspecialchars($this->pObj->pi_getLL('direction'));
			$confFleche = array();
			$confFleche['file'] = t3lib_extMgm::siteRelPath($this->pObj->extKey) . 'res/icons/arrow.png';
			$confFleche['file.']['height'] = '15px';
			$confFleche['height'] = '18px';
			$markers['PICTO_SEPARATOR'] = $this->pObj->cObj->IMAGE($confFleche);
		}
		return $content;
	}
	
	private function renderSiteConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_SITE_CONNECTION###');
		
		$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from'));
		$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to_stoppoint'));
		if ($section->departure->type == 'Site') {
			$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from_site'));
		}
		elseif ($section->arrival->type == 'Site') {
			$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to_site'));
		}

		$markers['START_NAME'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->name);
		$markers['ARRIVAL_NAME'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->name);
		$markers['START_CITY'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->city->name);
		$markers['ARRIVAL_CITY'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->city->name);
		
		$coordsDep = $this->getCoords($section->departure);
		$coordsArr = $this->getCoords($section->arrival);
		if ($coordsDep && $coordsArr)
			$markers['MAP'] = $this->renderMap($coordsDep, $coordsArr);
		return $content;
	}
	
	private function renderAddressConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_ADDRESS_CONNECTION###');
		
		$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from'));
		$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to_stoppoint'));
		if ($section->departure->type == 'Address') {
			$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from_address'));
		}
		else {
			$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to_address'));
		}
		
		$markers['START_NAME'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->name);
		$markers['ARRIVAL_NAME'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->name);
		$markers['START_CITY'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->city->name);
		$markers['ARRIVAL_CITY'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->city->name);
		
		$coordsDep = $this->getCoords($section->departure);
		$coordsArr = $this->getCoords($section->arrival);
		if ($coordsDep && $coordsArr)
			$markers['MAP'] = $this->renderMap($coordsDep, $coordsArr);
		return $content;
	}
	
	private function renderPersonnalCarConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_PERSONNAL_CAR_CONNECTION###');
		
		$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from'));
		$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to'));
		
		$markers['START_NAME'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->name);
		$markers['ARRIVAL_NAME'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->name);
		$markers['START_CITY'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->city->name);
		$markers['ARRIVAL_CITY'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->city->name);
		
		$coordsDep = $this->getCoords($section->departure);
		$coordsArr = $this->getCoords($section->arrival);
		if ($coordsDep && $coordsArr)
			$markers['MAP'] = $this->renderMap($coordsDep, $coordsArr);
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
	
	private function renderForcedLinkConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_STOP_POINT_CONNECTION###');
		
		$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from'));
		$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to_stoppoint'));
		
		$markers['START_NAME'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->name);
		$markers['ARRIVAL_NAME'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->name);
		$markers['START_CITY'] = htmlspecialchars($section->departure->{lcfirst($section->departure->type)}->city->name);
		$markers['ARRIVAL_CITY'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->city->name);
		
		$coordsDep = $this->getCoords($section->departure);
		$coordsArr = $this->getCoords($section->arrival);
		if ($coordsDep && $coordsArr)
			$markers['MAP'] = $this->renderMap($coordsDep, $coordsArr);
		return $content;
	}
	
	private function renderStopPointConnection(tx_icslibnavitia_Section $section, array & $markers) {
		$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_STOP_POINT_CONNECTION_WAIT###');
		
		$markers['FROM'] = htmlspecialchars($this->pObj->pi_getLL('from'));
		$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('wait_at'));
		if ($this->previousDuration) {
			$content = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_STOP_POINT_CONNECTION###');
			if ($section->duration->totalSeconds > 0) {
				$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to_stoppoint_wait'));
			}
			else {
				$markers['TO'] = htmlspecialchars($this->pObj->pi_getLL('to_stoppoint'));
			}
		}
		
		$departure = $section->departure;
		if ($this->previousDeparture)
			$departure = $this->previousDeparture;
		$markers['START_NAME'] = htmlspecialchars($departure->{lcfirst($departure->type)}->name);
		$markers['ARRIVAL_NAME'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->name);
		$markers['START_CITY'] = htmlspecialchars($departure->{lcfirst($departure->type)}->city->name);
		$markers['ARRIVAL_CITY'] = htmlspecialchars($section->arrival->{lcfirst($section->arrival->type)}->city->name);
		
		$coordsDep = $this->getCoords($departure);
		$coordsArr = $this->getCoords($section->arrival);
		if ($coordsDep && $coordsArr)
			$markers['MAP'] = $this->renderMap($coordsDep, $coordsArr);
		return $content;
	}
	
	private function renderUndefined(tx_icslibnavitia_Section $section, array & $markers) {
		//$sectionType = 'Liaison à pied entre un point géocodé sans nature et un point d\'arrêt';
		return '';
	}
	
	private function renderMap(tx_icslibnavitia_Coord $departure, tx_icslibnavitia_Coord $arrival) {
		$template = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_VIEW_MAP###');
		$parameters = $this->getMapParameters($departure, $arrival);
		$markers = array(
			'TITLE' => htmlspecialchars($this->pObj->pi_getLL('details_localize')),
			'STATIC_MAP' => htmlspecialchars('http://maps.googleapis.com/maps/api/staticmap?' . t3lib_div::implodeArrayForUrl( // TODO: Use request scheme.
				'',
				$parameters
			)),
		);
		return $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
	}
	
	private function renderDepartureMap(tx_icslibnavitia_Coord $departure) {
		$template = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_VIEW_MAP###');
		$parameters = $this->getMapParameters($departure, $departure);
		$markers = array(
			'TITLE' => htmlspecialchars($this->pObj->pi_getLL('details_localize_departure')),
			'STATIC_MAP' => htmlspecialchars('http://maps.googleapis.com/maps/api/staticmap?' . t3lib_div::implodeArrayForUrl( // TODO: Use request scheme.
				'',
				$parameters
			)),
		);
		return $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
	}
	
	private function renderArrivalMap(tx_icslibnavitia_Coord $arrival) {
		$template = $this->pObj->cObj->getSubpart($this->pObj->templates['details'], '###TEMPLATE_VIEW_MAP###');
		$parameters = $this->getMapParameters($arrival, $arrival);
		$markers = array(
			'TITLE' => htmlspecialchars($this->pObj->pi_getLL('details_localize_arrival')),
			'STATIC_MAP' => htmlspecialchars('http://maps.googleapis.com/maps/api/staticmap?' . t3lib_div::implodeArrayForUrl( // TODO: Use request scheme.
				'',
				$parameters
			)),
		);
		return $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
	}
	
	private function getMapParameters(tx_icslibnavitia_Coord $departure, tx_icslibnavitia_Coord $arrival) {
		$baseConf = $this->pObj->conf['details.']['map.'];
		$parameters = array(
			'size' => $baseConf['size'],
			'format' => $baseConf['format'],
			'maptype' => $baseConf['maptype'],
			'sensor' => 'false',
		);
		if (($departure->lat == $arrival->lat) && ($departure->lng == $arrival->lng)) {
			$parameters['zoom'] = $baseConf['zoom'];
			$parameters['center'] = $departure->lat . ',' . $departure->lng;
			$parameters['markers'] = $departure->lat . ',' . $departure->lng;
		}
		else {
			$parameters['markers'] = $departure->lat . ',' . $departure->lng . '|' . $arrival->lat . ',' . $arrival->lng;
			$parameters['visible'] = $departure->lat . ',' . $departure->lng . '|' . $arrival->lat . ',' . $arrival->lng;
			$segments = $this->dataProvider->getStreetNetwork($departure, $arrival);
		}
		$path = '';
		if ($segments) {
			$pathElements = array();
			for ($i = 0; $i < $segments->Count(); $i++) {
				$segment = $segments->Get($i);
				$start = $segment->startNode;
				$end = $segment->endNode;
				$start = $start->lat . ',' . $start->lng;
				$end = $end->lat . ',' . $end->lng;
				$pathElements[$start] = $end;
			}
			$first = array_diff(array_keys($pathElements), array_values($pathElements));
			if (!empty($first)) {
				$path = $first;
				$current = $first;
				while ($pathElements[$current]) {
					$current = $pathElements[$current];
					$path .= '|' . $current;
				}
			}
			else {
				$path = $departure->lat . ',' . $departure->lng . '|' . $arrival->lat . ',' . $arrival->lng;
			}
			$parameters['path'] = $path;
		}
		return $parameters;
	}
	
	function getObject($object, $key) {
		return $object->$key;
	}
	
	function getCoords($entryPoint) {
		if ($entryPoint->{lcfirst($entryPoint->type)}->coord)
			return $entryPoint->{lcfirst($entryPoint->type)}->coord;
		return $entryPoint->coord;
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