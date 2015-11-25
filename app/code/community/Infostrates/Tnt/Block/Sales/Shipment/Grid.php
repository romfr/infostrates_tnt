<?php
class Infostrates_Tnt_Block_Sales_Shipment_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_shipment_grid');
        $this->setDefaultSort('order_created_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * Retrieve collection class
     *
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'sales/order_shipment_grid_collection';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        
        $collection->distinct(true);
        $collection->getSelect()->columns(array('shipment_created_at' => 'main_table.created_at'));
        $collection->getSelect()->join(array('ost' => $collection->getTable('sales/shipment_track')), 'main_table.entity_id = ost.parent_id');
        $collection->addFieldToFilter('ost.carrier_code', array('like' => 'tnt%'));
        $collection->getSelect()->group('main_table.entity_id');
        $this->setCollection($collection);
        return parent::_prepareCollection();
        
        /* Bug Magento : le GROUP BY ne retourne pas le bon nombre d'enregistrement la correction se trouve dans /local/Varien/Data/Collection/Db.php -> getSelectCountSql */
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('sales')->__('Shipment #'),
            'index'     => 'increment_id',
            'type'      => 'text',
        ));

        $this->addColumn('shipment_created_at', array(
            'header'    => Mage::helper('sales')->__('Date Shipped'),
            'index'     => 'shipment_created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('order_increment_id', array(
            'header'    => Mage::helper('sales')->__('Order #'),
            'index'     => 'order_increment_id',
            'type'      => 'number',
        ));

        $this->addColumn('order_created_at', array(
            'header'    => Mage::helper('sales')->__('Order Date'),
            'index'     => 'order_created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
        ));

        $this->addColumn('total_qty', array(
            'header' => Mage::helper('sales')->__('Total Qty'),
            'index' => 'total_qty',
            'type'  => 'number',
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/order/shipment')) {
            return false;
        }
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('shipment_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        // Impression des étiquettes
        $this->getMassactionBlock()->addItem('pdfshipments_order', array(
             'label'=> Mage::helper('sales')->__('Imprimer les étiquettes'),
             'url'  => $this->getUrl('tnt/sales_impression/printMass'),
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/*', array('_current' => true));
    }
}
