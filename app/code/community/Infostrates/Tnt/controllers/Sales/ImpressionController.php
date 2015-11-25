<?php

class Infostrates_Tnt_Sales_ImpressionController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Additional initialization
     *
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Infostrates_Tnt');
    }


    /**
     * Shipping grid
     */
    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/tnt')
            ->_addContent($this->getLayout()->createBlock('tnt/sales_impression'))
            ->renderLayout();
    }
    
	public function getConfigData($field)
	{
        $path = 'carriers/tnt/'.$field;
        return Mage::getStoreConfig($path, Mage::app()->getStore());
	}
    
    protected function _processDownload($resource, $resourceType)
    {    	
    	$helper = Mage::helper('downloadable/download');

        $helper->setResource($resource, $resourceType);

        $fileName       = $helper->getFilename();
        $contentType    = $helper->getContentType();

        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true);

        if ($fileSize = $helper->getFilesize()) {
            $this->getResponse()
                ->setHeader('Content-Length', $fileSize);
        }

        if ($contentDisposition = $helper->getContentDisposition()) {
            $this->getResponse()
                ->setHeader('Content-Disposition', $contentDisposition . '; filename='.$fileName);
        }

        $this->getResponse()
            ->clearBody();
        $this->getResponse()
            ->sendHeaders();

        $helper->output();
    }
    
    protected function getEtiquetteUrl($shipmentsIds)
    {
    	//On récupère les infos d'expédition
        if (is_array($shipmentsIds))
        {
        	$path = Mage::getBaseDir('media').'/pdf_bt/';
        	$pdfDocs = array();
        	
        	for ($i = 0; $i < count($shipmentsIds); $i++)
            {
                $shipmentId = Mage::getModel('sales/order_shipment_track')->load($shipmentsIds[$i])->getParentId();
            	$orderNum = Mage::getModel('sales/order_shipment')->load($shipmentId)->getOrder()->getRealOrderId();        		

        		// Array of the pdf files need to be merged
        		$pdfDocs[] = $path.$orderNum.'.pdf';
            }
            
            $filename = $path."tnt_pdf.pdf";
            
            $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$filename ";
			//Add each pdf file to the end of the command
			for($i=0; $i<count($pdfDocs);$i++) {
			    $cmd .= $pdfDocs[$i]." ";
			}
			$result = shell_exec($cmd);
			$filename = "tnt_pdf.pdf";
        }
        else
        {
            $shipmentId = $shipmentsIds;
            
        	$orderNum = Mage::getModel('sales/order_shipment')->load($shipmentId)->getOrder()->getRealOrderId();
        	
        	$filename = $orderNum.'.pdf';
        };
        return $filename;
    }
    
    public function printMassAction()
    {
        $path = Mage::getBaseUrl('media').'pdf_bt/';
    	$shipmentsIds = $this->getRequest()->getPost('shipment_ids');
        
        try {
            $filename = $this->getEtiquetteUrl($shipmentsIds);

            $this->_processDownload($path.$filename, 'url');
            exit(0);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError(Mage::helper('tnt')->__('Impossible de récupérer les BT : '.$filename));
        }
        return $this->_redirectReferer();
    }

    public function printAction()
    {
        $path = Mage::getBaseUrl('media').'pdf_bt/';
    	$shipmentId = $this->getRequest()->getParam('shipment_id');

        try {
        	$filename = $this->getEtiquetteUrl($shipmentId);
        	 
            $this->_processDownload($path.$filename, 'url');
            exit(0);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError(Mage::helper('tnt')->__('Impossible de récupérer le BT : '.$filename));
        }
        return $this->_redirectReferer();
    }
    
}