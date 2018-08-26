<?php
/**
 * Created by PhpStorm.
 * User: micahel
 * Date: 24.7.18
 * Time: 20:14
 */

namespace Pushketing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class PushketingObserver implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observe) {
        $item = $observe->getEvent()->getData();
        $push = new \Pushketing();
        $push->postTag('addToCart', rand(0, 10), $_COOKIE['user']);
    }

}