<?php

/**
 * Example function for extending the JobControl plugin with extra labels.
 * See manual for info on how to use this file.
 *
 * @param array $markerArray The original markerArray
 * @param array $conf Contains 2 important elements, with keys row and parent. See example code below.
 * @return array The changed (extended) markerArray
 */
function user_labelArrayFunction($markerArray, $conf) {
    // Add to typoscript setup:
    // plugin.tx_dmmjobcontrol_pi1._LOCAL_LANG.default.logo_label = Company logo
    // plugin.tx_dmmjobcontrol_pi1._LOCAL_LANG.nl.logo_label = Bedrijfslogo
    // plugin.tx_dmmjobcontrol_pi1._LOCAL_LANG.default.test_label = Testing

    $markerArray['###LOGO_LABEL###'] = $conf['parent']->pi_getLL('logo_label');
    $markerArray['###TEST_LABEL###'] = $conf['parent']->pi_getLL('test_label');
    return $markerArray;
}

?>
