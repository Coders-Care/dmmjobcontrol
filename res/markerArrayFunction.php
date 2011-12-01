<?php

/**
 * Example function for extending the detail view of the JobControl plugin with extra markers.
 * See manual for info on how to use this file.
 *
 * In these examples, replace jobcontrolextension by the extension key you've chosen in the kickstarter.
 * If in one of these examples it is prefixed with tx_, you should keep that prefix as well.
 *
 * @param array $markerArray The original markerArray
 * @param array $conf Contains 2 important elements, with keys row and parant. See example code below.
 * @return array The changed (extended) markerArray
 */
function user_markerArrayFunction($markerArray, $conf){
	// The row from the database containing the job.
	$row = $conf['row'];

	// Showing the contents from a text field is very easy.
	$markerArray['###CONTACT_NAME###'] = $row['tx_jobcontrolextension_contact_name'];

	// You can add stdWrap functions on your own fields. For example, you could add something like this in your TypoScript setup:
	// plugin.tx_dmmjobcontrol_pi1.contact_name_stdWrap.case = upper
	// (that dot in $conf['parent']->conf['contact_name_stdWrap.'] is not a typo, it really must be there..)
	$markerArray['###CONTACT_NAME###'] = $conf['parent']->cObj->stdWrap($row['tx_jobcontrolextension_contact_name'], $conf['parent']->conf['contact_name_stdWrap.']);

	// Want to use the default parse function for RTE content? Use the default general_stdWrap configuration supplied by JobControl.
	$markerArray['###CONTACT_INFORMATION###'] = $conf['parent']->cObj->stdWrap($row['tx_jobcontrolextension_contact_information'], $conf['parent']->conf['general_stdWrap.']);

	// Showing an image with a set max height and width
	$image['file'] = 'uploads/tx_jobcontrolextension/'.$row['tx_jobcontrolextension_logo'];
	$image['file.']['maxW'] = 300;
	$image['file.']['maxH'] = 150;
	$markerArray['###LOGO###'] = '<img src="'.$conf['parent']->cObj->IMG_RESOURCE($image).'" />';

	// Showing the contents from a selectbox is a little more complicated since the actual value is not stored
	// in the database, instead only the id is stored and the value comes from the locallang_db.xml file from
	// your extending plugin.
	$conf['parent']->lang->includeLLFile('EXT:jobcontrolextension/locallang_db.xml');
	$markerArray['###CONTACT_TYPE###'] = $conf['parent']->lang->getLL('tx_dmmjobcontrol_job.tx_jobcontrolextension_contact_type.I.'.$row['tx_jobcontrolextension_contact_type']);

	// IMPORTANT! Return the extended markerArray.
	// If you don't return this, nothing will be parsed by JobControl at all.
	return $markerArray;
}

?>