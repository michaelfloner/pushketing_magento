<?php
/**
 * Autor Michael Floner
 */

namespace Pushketing\Block;

class Pushketing extends \Magento\Framework\View\Element\Template
{
    protected $helper;

    protected $request;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        \Magento\Framework\App\Request\Http $request,
        \Magento\GoogleAnalytics\Helper\Data $helper)
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->request = $request;

    }

    public function getMagentoVersion()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();
        return $version;
    }

    public function getAction()
    {
        $get_action = $this->request->getFullActionName();
        if(method_exists($this, $get_action)) {
            $data = $this->$get_action();
            return array($data,$get_action);
        }

    }

    protected function catalog_product_view()
    {
        $cart_items = $this->helper->getProductDetails();
        return json_encode($cart_items);
    }

    protected function catalog_category_view()
    {
        $t_getCategoryProduct = $this->helper->getCategoryProduct();
        return json_encode($t_getCategoryProduct);
    }

    protected function checkout_cart_index()
    {
        $cart_items = $this->helper->getCartpageInfo();
        return json_encode($cart_items);
    }

    protected function checkout_onepage_success()
    {
        $tvc_order_obj = $this->helper->getOrderDetails();
        return json_encode($tvc_order_obj);
    }

    /**
     * Client POST API request sending tag (keyword=value) about specific customer.
     *
     * @param $keyword
     * @param $value
     * @param $customer
     *
     */
    public function postTag($keyword, $value, $customer) {
        $tag = array(
            'keyword' => $keyword,
            'value' => $value
        );
        $request = array(
            'timestamp' => time(),
            'token' => $this->helper->getPushketingToken(),
            'subscriber_id' => $customer,
            'tag' => array($tag)
        );
        $ch = curl_init($this->helper->getEndpointUrl());
        $payload = json_encode($request);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers = array(
            'Content-Type: application/json'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

}
