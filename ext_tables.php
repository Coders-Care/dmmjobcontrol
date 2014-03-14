<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA['tx_dmmjobcontrol_job'] = array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_job',
        'label'     => 'job_title',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY crdate DESC',
        'sortby' => 'sorting',
        'delete' => 'deleted',
		'searchFields' =>'job_title, employer,employer_description, location, region, short_job_description, job_description, experience, job_requirements, job_benefits',
        'enablecolumns' => array (
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ),
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_dmmjobcontrol_job.gif',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, reference, job_title, employer, employer_description, location, region, short_job_description, job_description, experience, job_requirements, job_benefits, apply_information, salary, job_type, contract_type, sector, category, discipline, education, contact',
    )
);

$TCA['tx_dmmjobcontrol_sector'] = array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_sector',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY name',
        'sortby' => 'sorting',
		'searchfield' => 'name',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_dmmjobcontrol_sector.gif',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, name',
    )
);

$TCA['tx_dmmjobcontrol_category'] = array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_category',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
		'searchfield' => 'name',
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY name',
        'sortby' => 'sorting',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_dmmjobcontrol_category.gif',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, name',
    )
);

$TCA['tx_dmmjobcontrol_discipline'] = array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_discipline',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
		'searchfield' => 'name',
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY name',
        'sortby' => 'sorting',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_dmmjobcontrol_discipline.gif',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, name',
    )
);

$TCA['tx_dmmjobcontrol_region'] = array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_region',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
		'searchfield' => 'name',
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY name',
        'sortby' => 'sorting',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_dmmjobcontrol_region.gif',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, name',
    )
);

$TCA['tx_dmmjobcontrol_education'] = array (
    'ctrl' => array (
        'title'     => 'LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_education',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
		'searchfield' => 'name',
        'languageField'            => 'sys_language_uid',
        'transOrigPointerField'    => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY name',
        'sortby' => 'sorting',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_dmmjobcontrol_education.gif',
    ),
    'feInterface' => array (
        'fe_admin_fieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, name',
    )
);

$TCA["tx_dmmjobcontrol_contact"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_contact',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
		'searchfield' => 'name',
        'cruser_id' => 'cruser_id',
        'default_sortby' => "ORDER BY name",
        'sortby' => 'sorting',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_dmmjobcontrol_contact.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "name, address, phone, email",
    )
);

t3lib_div::loadTCA('tt_content');

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(array('LLL:EXT:dmmjobcontrol/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/','JobControl');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:dmmjobcontrol/flexform_ds.xml');

?>
