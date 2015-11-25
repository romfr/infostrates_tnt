<?php
$installer = $this;
$installer->startSetup();

$installer->run("
INSERT INTO {$installer->getTable('core/config_data')} (path,value) VALUES ('carriers/tnt/tnt_max_weight','60');
UPDATE {$installer->getTable('core/config_data')} SET path = 'carriers/tnt/max_package_weight', value = '30' WHERE path = 'carriers/tnt/max_package_weight';");

$installer->endSetup();
?>