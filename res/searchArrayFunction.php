<?php

/**
 * Example function for extending the search form of the JobControl plugin with extra markers.
 * See manual for info on how to use this file.
 *
 * In these examples, replace jobcontrolextension by the extension key you've chosen in the kickstarter.
 * If in one of these examples it is prefixed with tx_, you should keep that prefix as well.
 *
 * @param array $markerArray The original markerArray
 * @param array $conf Contains 1 important element, with key 'parent'. See example code below.
 * @return array The changed (extended) markerArray
 */
function user_searchArrayFunction($markerArray, $conf) {
	// In this example, we've added a new "MM database relation" field called "country" to JobControl.
	// Since all the needed text is added to the TCA automatically, getting the selectbox is very easy:
	$markerArray['###COUNTRY_SELECT###'] = $conf['parent']->getFormSelect('tx_jobcontrolextension_country');

	// IMPORTANT! Return the extended markerArray.
	// If you don't return this, nothing will be parsed by JobControl at all.
	return $markerArray;
}

?>
