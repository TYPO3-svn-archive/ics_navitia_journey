<?php

########################################################################
# Extension Manager/Repository config file for ext "ics_navitia_journey".
#
# Auto generated 19-08-2011 15:44
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

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
			'ics_libnavitia' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:12:{s:9:"ChangeLog";s:4:"fcd6";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"6d62";s:14:"ext_tables.php";s:4:"8c5f";s:16:"locallang_db.xml";s:4:"0e28";s:19:"doc/wizard_form.dat";s:4:"c682";s:20:"doc/wizard_form.html";s:4:"6435";s:38:"pi1/class.tx_icsnavitiajourney_pi1.php";s:4:"80b1";s:17:"pi1/locallang.xml";s:4:"8321";s:28:"static/journey/constants.txt";s:4:"d41d";s:24:"static/journey/setup.txt";s:4:"d41d";}',
);

?>