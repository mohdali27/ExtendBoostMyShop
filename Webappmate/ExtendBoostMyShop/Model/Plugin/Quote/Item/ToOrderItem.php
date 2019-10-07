<?php
namespace Webappmate\ExtendBoostMyShop\Model\Plugin\Quote\Item;

class ToOrderItem
{
   public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        /** @var $orderItem Item */
        $orderItem = $proceed($item, $additional);
        $orderItem->SetData('supplier_rate',$item->getData('supplier_rate'));
        return $orderItem;
    }
}
