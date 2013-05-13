<?php

/**
 * Example function for extending the apply form of the JobControl plugin with extra markers.
 * See manual for info on how to use this file.
 *
 * @param array $markerArray The original markerArray
 * @param array $conf Contains 1 important element, with key 'parent'. See example code below.
 * @return array The changed (extended) markerArray
 */
function user_applyArrayFunction($markerArray, $conf) {
    // This example extends the apply form with an element like this:
    //
    // <tr class="dmmjobcontrol_apply_tr">
    //   <td class="dmmjobcontrol_apply_td1">
    //     ###TEST_LABEL###
    //   </td>
    //   <td class="dmmjobcontrol_apply_td2">
    //     <input type="text" name="###TEST_NAME###" value="###TEST_VALUE###" class="dmmjobcontrol_input" />
    //   </td>
    // </tr>
    //
    // Please note that the label (TEST_LABEL) should be setup with the
    // labelArrayFunction function. See documentation.

    $markerArray['###TEST_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][test]';
    $markerArray['###TEST_VALUE###'] = $conf['parent']->piVars['apply']['test'];

    // IMPORTANT! Return the extended markerArray.
    // If you don't return this, nothing will be parsed by JobControl at all.
    return $markerArray;
}

?>
