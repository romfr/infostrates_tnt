<?php
class Infostrates_Tnt_Block_Onepage_Shipping_Method_Available extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
	protected $_itemRenders = array();

    public function __construct()
    {
        parent::__construct();
        
        //Rendu par défaut
        $this->addItemRender(
            'default',
            'tnt/onepage_shipping_method_available',
            'tnt/onepage/shipping_method/available/default.phtml'
        );
    }

    /**
     * Ajoute une correspondance dans la table des rendus des modes de livraison.
     * Cela permet de personnaliser l'affichage d'un mode de livraison parmi la
     * liste des modes proposés lors du passage d'une commande.
     * @param   string $type
     * @param   string $block
     * @param   string $template
     * @return  Infostrates_tnt_Block_Onepage_Shipping_Method_Available
     */
    public function addItemRender($type, $block, $template)
    {
        $this->_itemRenders[$type] = array(
            'block' => $block,
            'template' => $template,
            'blockInstance' => null
        );
        return $this;
    }
    
    /**
     * Retourne le renderer approprié selon le mode de livraison.
     * Le renderer par défaut est "default".
     * @param   string $type exemples : default|tnt
     * @return  array
     */
    public function getItemRenderer($type)
    {
        if (!isset($this->_itemRenders[$type])) {
            $type = 'default';
        }
        if (is_null($this->_itemRenders[$type]['blockInstance'])) {
             $this->_itemRenders[$type]['blockInstance'] = $this->getLayout()
                ->createBlock($this->_itemRenders[$type]['block'])
                    ->setTemplate($this->_itemRenders[$type]['template'])
                    ->setRenderedBlock($this);
        }

        return $this->_itemRenders[$type]['blockInstance'];
    }
    
    /**
     * Retourne le code html du mode de livraison donné.
     * @param   Mage_Shipping_Model_Carrier_Abstract $item
     * @return  string
     */
    public function getItemHtml($item)
    {
        //le code html retourné dépend du mode de livraison
        $renderer = $this->getItemRenderer($item->getMethod())->setRate($item);
        return $renderer->toHtml();
    } 
}
