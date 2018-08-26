<?php
/**
 * Autor: Michael Floner
 */
namespace Pushketing\Helper;

use Magento\Store\Model\Store;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use phpDocumentor\Reflection\Types\Self_;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $push_blockFactory;

    /**
     * path to pushketing app is active.
     */
    const PATH_ACTIVE = 'pushketing/general/enable';

    /**
     * path to pushketing app token for api.
     */
    const PATH_TOKEN_ID = 'pushketing/general/pushketing_id';

    const END_POINT_URL = 'https://pushketing.online/api/tag';

    protected $push_registry;

    protected $_scopeConfig;


    public function __construct(\Magento\Framework\Registry $registry,
                                \Magento\Framework\View\Element\BlockFactory $blockFactory,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->push_registry = $registry;
        $this->push_blockFactory = $blockFactory;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Check if pushketing ready for to use.
     *
     * @param null $store
     *
     * @return bool
     */
    public function isPushketingAvailable($store = null)
    {
        $pushketingId = $this->_scopeConfig->getValue(self::PATH_TOKEN_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        return $pushketingId && $this->_scopeConfig->isSetFlag(self::PATH_ACTIVE, 'default');

    }

    public function getEndpointUrl()
    {
        return self::END_POINT_URL;
    }

    /**
     * Retun pushketing ID.
     *
     * @param null $store
     *
     * @return string
     */
    public function getPushketingToken($store = null)
    {
        if ($this->isPushketingAvailable()) {
            return $this->_scopeConfig->getValue(self::PATH_TOKEN_ID, ScopeInterface::SCOPE_STORE, $store);
        }
    }

    protected function getCurrentProduct()
    {
        return $this->push_registry->registry('current_product');
    }

    public function getProductDetails(){

        $push_prod_details = $this->getCurrentProduct()->getSku();


        return $push_prod_details;
    }

    public function getCartpageInfo(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $items = $this->cart->getQuote()->getAllVisibleItems();
        $push_cart = [];
        foreach($items as $product){
            $push_cat_ids = $product->getProduct()->getCategoryIds();
            $push_category = $this->getCategoryByIds($push_cat_ids);
            $prod_arr = ['push_id' => $product->getProduct()->getSku(),
                'push_i'    => $product->getId(),
                'push_name' => $product->getProduct()->getName(),
                'push_p'    => $product->getProduct()->getPrice(),
                'push_qty'  => $product->getQty(),
                'push_c'    => $push_category
            ];
            array_push($push_cart, $prod_arr);
        }
        return $push_cart;
    }

    public function getOrderDetails(){
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId=$order->getEntityId();
        $order->getIncrementId();
        $push_order = $this->orderRepository->get($orderId);
        $push_order_obj = [];
        foreach($push_order->getAllItems() as $item){
            $product = $item->getProduct();
            $push_cat_ids = $item->getProduct()->getCategoryIds();
            $push_category = $this->getCategoryByIds($push_cat_ids);
            $prod_arr = ['push_id' => $product->getSku(),
                'push_i'    => $product->getId(),
                'push_name' => $product->getName(),
                'push_Qty'  => $item->getQtyOrdered()
            ];
            array_push($push_order_obj, $prod_arr);
        }
        return $push_order_obj;

    }

    public function getTransactionDetails(){
        $lastOrderId = $this->checkoutSession->getLastOrderId();
        $push_order = $this->orderRepository->get($lastOrderId);
        $payment_method = $this->checkoutSession->getLastRealOrder()->getPayment()->getMethod();
        $prod_arr = ['push_id' =>  $push_order->getId(),
            'push_revenue'        => $push_order->getGrandTotal(),
            'push_affiliate'      => $this->getAffiliationName(),
            'push_tt'             => $push_order->getTaxAmount(),
            'push_ts'             => $push_order->getShippingAmount(),
            'push_payment'        => $payment_method,
            'push_shipping'       => $push_order->getShippingDescription()
        ];
        return $prod_arr;
    }

    public function getCategoryProduct()
    {
        $push_catProd = [];
        $push_prod_data = $this->push_blockFactory->createBlock('Magento\Catalog\Block\Product\ListProduct');
        $push_prod_data->getLoadedProductCollection()->setPageSize($this->setPageLimit());
        $push_prod_data->getLoadedProductCollection()->setCurPage($this->getCurrentPage());
        foreach ($push_prod_data->getLoadedProductCollection() as $product){
            $prod_arr = ['push_id' => $product->getSku(),
                'push_i'    => $product->getId(),
                'push_name' => $product->getName(),
            ];
            array_push($push_catProd, $prod_arr);
        }
        return $push_catProd;
    }

    protected function setPageLimit(){
        $pageSize=($this->_request->getParam('limit'))? $this->_request->getParam('limit') : 9;
        return $pageSize;
    }

    protected function getCurrentPage(){
        $page=($this->_request->getParam('p'))? $this->_request->getParam('p') : 1;
        return $page;
    }
}