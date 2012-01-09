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

	public function getSearchForm($dataProvider = null, $entryPointStart = null, $entryPointArrival = null) {
		$templatePart = $this->pObj->templates['search'];
		$template = $this->pObj->cObj->getSubpart($templatePart, '###TEMPLATE_JOURNEY_SEARCH###');
		
		if(t3lib_extMgm::isLoaded('ics_header_dyn')) {
			$headerDyn = t3lib_div::makeInstance('tx_icsheaderdyn_pi1');
			$this->dataTheme = $headerDyn->getPageHeaderColor();
		}
		else {
			$this->dataTheme = 'a';
		}
		
		$markers = array(
			'PREFIXID' => htmlspecialchars($this->pObj->prefixId),
			'SEARCH' => htmlspecialchars($this->pObj->pi_getLL('menu_search')),
			'DATA_THEME' => $this->dataTheme,
			'RESULTS' => htmlspecialchars($this->pObj->pi_getLL('menu_results')),
			'DETAILS' => htmlspecialchars($this->pObj->pi_getLL('menu_details')),
			'ACTION_URL' => htmlspecialchars($this->pObj->pi_getPageLink($GLOBALS['TSFE']->id)),
			'JOURNEY_DATE' => htmlspecialchars($this->pObj->pi_getLL('search_journeyDate')),
			'START_AT' => htmlspecialchars($this->pObj->pi_getLL('startAt')),
			'ARRIVAL_AT' => htmlspecialchars($this->pObj->pi_getLL('arrivalAt')),
			'PREFERENCES' => htmlspecialchars($this->pObj->pi_getLL('preference')),
			'MODE_TYPE' => htmlspecialchars($this->pObj->pi_getLL('preference_mode')),
			'CRITERIA' => htmlspecialchars($this->pObj->pi_getLL('preference_criteria')),
			'SUBMIT' => htmlspecialchars($this->pObj->pi_getLL('search_submit')),
			'SELECTED_0' => '',
			'SELECTED_1' => '',
			'HIDDEN_FIELDS' => $this->pObj->getHiddenFields(),
		);
		
		if(isset($this->pObj->piVars['isStartTime'])) {
			$markers['SELECTED_' . $this->pObj->piVars['isStartTime']] = ' checked';
		}
		else {
			$markers['SELECTED_1'] = ' checked';
		}
		
		$markers['START_FORM'] = $this->makeFormPart(true);
		$template = $this->pObj->cObj->substituteSubpart(
			$template, 
			'###CONFIRM_SOLUTIONS_START###',
			$this->makeSolutionPart($entryPointStart, true)
		);
		$markers['ARRIVAL_FORM'] = $this->makeFormPart(false);
		$template = $this->pObj->cObj->substituteSubpart(
			$template, 
			'###CONFIRM_SOLUTIONS_ARRIVAL###',
			$this->makeSolutionPart($entryPointArrival, false)
		);
		
		if(!isset($this->pObj->piVars['date'])) {
			$markers['DATE_SEL'] = htmlspecialchars(date('d/m/Y'));
			$markers['TIME_SEL'] = htmlspecialchars(date('H:i'));
		}
		else {
			$markers['DATE_SEL'] = $this->pObj->piVars['date'];
			$markers['TIME_SEL'] = $this->pObj->piVars['hour'];
		}
		

		$template = $this->pObj->replaceModes($template);
		$template = $this->pObj->replaceCriteria($template);

		$content .= $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $content;
	}
	
	private function makeFormPart($isStart) {
		$direction = ($isStart) ? 'start' : 'arrival';
		$markers = array(
			'PREFIXID' => htmlspecialchars($this->pObj->prefixId),
			'NAME' => htmlspecialchars($this->pObj->pi_getLL('search_name')),
			'CITY' => htmlspecialchars($this->pObj->pi_getLL('search_city')),
			'direction' => $direction,
		);
		if ($isStart) {
			$markers['TITLE'] = htmlspecialchars($this->pObj->pi_getLL('search_startAddress'));
			$markers['NAME_VALUE'] = htmlspecialchars($this->pObj->piVars['startName']);
			$markers['CITY_VALUE'] = htmlspecialchars($this->pObj->piVars['startCity']);
		}
		else {
			$markers['TITLE'] = htmlspecialchars($this->pObj->pi_getLL('search_arrivalAddress'));
			$markers['NAME_VALUE'] = htmlspecialchars($this->pObj->piVars['arrivalName']);
			$markers['CITY_VALUE'] = htmlspecialchars($this->pObj->piVars['arrivalCity']);
		}
		$template = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###LOCATION_FORM_PART###');
		$noUserEntry = false;
		if ($this->pObj->conf['useGeolocation'] && ($this->pObj->conf['arrivalGeolocation'] || $isStart)) {
			if (!isset($this->pObj->conf['refreshDelay']))
				$this->pObj->conf['refreshDelay'] = 120;
			$geoloc = new tx_icslibgeoloc_GeoLocation();
			if (isset($this->pObj->conf['errorCallback']));
				$geoloc->error = $this->pObj->conf['errorCallback'];
			$name = addslashes($this->pObj->prefixId . '_' . $direction . 'Name');
			$city = addslashes($this->pObj->prefixId . '_' . $direction . 'City');
			$z = $geoloc->ManualForm;
			if ((($geoloc->Position === false) || (($this->pObj->conf['refreshDelay']) && ($geoloc->Update + $this->pObj->conf['refreshDelay'] < time()))) && 
				(!$geoloc->IsDenied)) {
				$geoloc->success = <<<EOJS
function(position) {
	var lat = position.coords.latitude;
	var lng = position.coords.longitude;
###POSITION###
}
EOJS;
				$geoloc->maxAge = intval($this->pObj->conf['refreshDelay']);
				$geoloc->requireGps = true;
				$call = $geoloc->JsCall . '(); return false;';
			}
			else {
				$lat = $geoloc->Position['latitude'];
				$lng = $geoloc->Position['longitude'];
				$call = <<<EOJS
{
	var lat = $lat;
	var lng = $lng;
###POSITION###
}
return false;
EOJS;
			}
			$texts = json_encode(array(
				'zero' => $this->pObj->pi_getLL('search_myPosition.zero'),
				'error' => $this->pObj->pi_getLL('search_myPosition.error'),
			));
			$call = str_replace(
				'###POSITION###',
				<<<EOJS
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({ 'location': new google.maps.LatLng(lat, lng) }, function(results, status) {
		var lang = $texts;
		var address = '';
		var city = '';
		switch (status) {
			case google.maps.GeocoderStatus.OK:
				var components = [];
				for (var i = 0; i < results[0].address_components.length; i++) {
					var el = results[0].address_components[i];
					for (var j = 0; j < el.types.length; j++) {
						switch (el.types[j])
						{
							case 'street_number':
								components.push(el.long_name);
								j = el.types.length;
								break;
							case 'route':
								components.push(el.long_name);
								j = el.types.length;
								break;
							case 'locality':
								city = el.long_name;
								j = el.types.length;
								break;
						}
					}
				}
				address = components.join(' ');
				break;
			case google.maps.GeocoderStatus.ZERO_RESULTS:
				address = lang.zero;
				break;
			default:
				address = lang.error;
				break;
		}
		document.getElementById('$name').value = address;
		document.getElementById('$city').value = city;
	});
EOJS
				,
				$call
			);
			$positionTemplate = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###POSITION_SELECT###');
			$positionMarkers = array(
				'POSITION_TEXT' => htmlspecialchars($this->pObj->pi_getLL('search_myPosition')),
				'POSITION_URL' => '#',
				'CLICK_EVENT' => htmlspecialchars($call),
			);
			$positionTemplate = $this->pObj->cObj->substituteMarkerArray($positionTemplate, $positionMarkers, '###|###');
			$template = $this->pObj->cObj->substituteSubpart($template, '###POSITION###', $positionTemplate);
		}
		else
			$template = $this->pObj->cObj->substituteSubpart($template, '###POSITION###', '');
		if (!$noUserEntry) {
			$content = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###LOCATION_FORM_USER_ENTRY###');
		}
		$template = $this->pObj->cObj->substituteMarker($template, '###FORM_PART_CONTENT###', $content);
		$result = $this->pObj->cObj->substituteMarkerArray($template, $markers, '###|###');
		return $result;
	}
	
	private function makeSolutionPart($entryPoint, $isStart) {
		$content = '';
		$markers = array(
			'NAME_SUFFIX' => ($isStart ? 'Start' : 'Arrival')
		);
		if ($entryPoint != null) {
			if ($entryPoint->Count() == 0) {
				$noSolutionTemplate = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###CONFIRM_NO_SOLUTION###');
				$markers['NO_SOLUTION'] = htmlspecialchars($this->pObj->pi_getLL('nosolution'));
				$content = $this->pObj->cObj->substituteMarkerArray($noSolutionTemplate, $markers, '###|###');
			}
			elseif ($entryPoint->Count() == 1) {
				$entryPoint = $entryPoint->Get(0);
				$oneSolutionTemplate = $this->pObj->cObj->getSubpart($this->pObj->templates['search'], '###CONFIRM_ONE_SOLUTION###');
				
				$markers['ONE_SOLUTION'] = htmlspecialchars($this->pObj->pi_getLL('onesolution'));
				
				$markers['CITY'] = htmlspecialchars($entryPoint->cityName);
				
				$stop = $entryPoint->{lcfirst($entryPoint->type)};

				$markers['STOPPOINT'] = htmlspecialchars($entryPoint->address->type->name) .' ' . htmlspecialchars($stop->name);
				
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
					$locMarkers['ENTRYPOINT'] = htmlspecialchars($index);
					$locMarkers['CITY'] = htmlspecialchars($entryPoint->cityName);
					$locMarkers['STOPPOINT'] = htmlspecialchars($entryPoint->address->type->name) .' ' . htmlspecialchars($stop->name);
					$locMarkers['RECORDNUMBER'] = htmlspecialchars($index);
					$locMarkers['ENTRYPOINT_TYPE'] = htmlspecialchars($this->pObj->pi_getLL('entryPointType_' . strtolower($entryPoint->type)));
					if ($entryPoint->type == 'Address') {
						$data = array();
						$data['type'] = $locMarkers['ENTRYPOINT_TYPE'];
						if ($entryPoint->hangList->start) {
							$data['range'] = htmlspecialchars(sprintf($this->pObj->pi_getLL('entryPointType_address_range'), $entryPoint->hangList->start, $entryPoint->hangList->end));
						}
						else {
							$data['range'] = htmlspecialchars($this->pObj->pi_getLL('entryPointType_address_center'));
						}
						$locCObj = t3lib_div::makeInstance('tslib_cObj');
						$locCObj->start($data);
						$locMarkers['ENTRYPOINT_TYPE'] = $locCObj->stdWrap($this->pObj->conf['address_stdWrap'], $this->pObj->conf['address_stdWrap.']);
					}
					$index++;
					$solutionItem .= $this->pObj->cObj->substituteMarkerArray($solutionsItemTemplate, $locMarkers, '###|###');
				}
				$moreSolutionsTemplate = $this->pObj->cObj->substituteSubpart($moreSolutionsTemplate, '###LIST_MORE_SOLUTIONS###', $solutionItem);
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