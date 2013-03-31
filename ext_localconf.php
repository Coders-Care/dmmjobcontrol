<?php

if (!defined ('TYPO3_MODE'))     die ('Access denied.');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_dmmjobcontrol_job=1
');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_dmmjobcontrol_sector=1
');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_dmmjobcontrol_category=1
');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_dmmjobcontrol_discipline=1
');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_dmmjobcontrol_region=1
');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_dmmjobcontrol_education=1
');

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_dmmjobcontrol_contact=1
');

## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
    tt_content.CSS_editor.ch.tx_dmmjobcontrol_pi1 = < plugin.tx_dmmjobcontrol_pi1.CSS_editor
',43);


t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_dmmjobcontrol_pi1.php','_pi1','list_type',1);

?>
