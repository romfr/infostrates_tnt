<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('order', 'tnt_infos_comp', array(
    'type' => 'varchar',
    'input' => 'text',
    'default' => 0,
    'label' => 'TNT infos comp',
    'required' => 0,
    )
);

$installer->endSetup();
