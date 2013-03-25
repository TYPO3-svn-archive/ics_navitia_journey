<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ics_navitia_journey".
 *
 * Auto generated 25-03-2013 15:48
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'NAViTiA Journey display',
	'description' => 'This extension display a journey module using NAViTiA.',
	'category' => 'plugin',
	'author' => 'In Cité Solution',
	'author_email' => 'technique@in-cite.net',
	'shy' => '',
	'dependencies' => 'ics_libnavitia',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-0.0.0',
			'ics_libnavitia' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'ics_linepicto' => '',
			'ics_libgeoloc' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:29:{s:9:"ChangeLog";s:4:"aa9a";s:16:"ext_autoload.php";s:4:"f51a";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"6d62";s:14:"ext_tables.php";s:4:"6e1a";s:16:"locallang_db.xml";s:4:"99dd";s:10:"README.txt";s:4:"ee2d";s:43:"Classes/class.icsnavitiajourney_details.php";s:4:"082f";s:43:"Classes/class.icsnavitiajourney_results.php";s:4:"e776";s:42:"Classes/class.icsnavitiajourney_search.php";s:4:"6682";s:38:"pi1/class.tx_icsnavitiajourney_pi1.php";s:4:"aa96";s:17:"pi1/locallang.xml";s:4:"e7e7";s:24:"pi1/static/constants.txt";s:4:"c684";s:20:"pi1/static/setup.txt";s:4:"2847";s:19:"res/icons/arrow.png";s:4:"05cd";s:21:"res/icons/bicycle.png";s:4:"4a6c";s:17:"res/icons/bus.png";s:4:"2efb";s:17:"res/icons/car.png";s:4:"dbb6";s:19:"res/icons/coach.png";s:4:"e563";s:21:"res/icons/default.png";s:4:"25a6";s:24:"res/icons/localtrain.png";s:4:"4f54";s:19:"res/icons/metro.png";s:4:"2d06";s:19:"res/icons/train.png";s:4:"0812";s:18:"res/icons/tram.png";s:4:"388a";s:18:"res/icons/walk.png";s:4:"dbab";s:43:"res/templates/template_journey_details.html";s:4:"be74";s:40:"res/templates/template_journey_plan.html";s:4:"8925";s:43:"res/templates/template_journey_results.html";s:4:"2b10";s:42:"res/templates/template_journey_search.html";s:4:"1af4";}',
	'suggests' => 'ics_linepicto,ics_libgeoloc',
);

?>