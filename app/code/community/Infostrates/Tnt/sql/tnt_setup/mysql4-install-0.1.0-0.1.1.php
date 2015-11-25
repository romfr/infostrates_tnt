<?php
$installer = $this;
$installer->startSetup();

$installer->run("
INSERT INTO {$installer->getTable('core/config_data')} (path,value) VALUES ('carriers/tnt/title','TNT EXPRESS');
INSERT INTO {$installer->getTable('core/config_data')} (path,value) VALUES ('carriers/tnt/max_package_weight','20');
INSERT INTO {$installer->getTable('core/config_data')} (path,value) VALUES ('carriers/tnt/allowed_methods','J,JD,JZ');
INSERT INTO {$installer->getTable('core/config_data')} (path,value) VALUES ('carriers/tnt/sallowspecific','0');
INSERT INTO {$installer->getTable('core/config_data')} (path,value) VALUES ('carriers/tnt/specificcountry','');
ALTER TABLE {$installer->getTable('sales_flat_quote_address')} ADD `tnt_infos_comp` varchar(255);
ALTER TABLE {$installer->getTable('sales_flat_order_address')} ADD `tnt_infos_comp` varchar(255);
");

$installer->endSetup();
?>