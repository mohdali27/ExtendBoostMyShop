<?php

namespace Webappmate\ExtendBoostMyShop\Model;

class DropShip extends \BoostMyShop\DropShip\Model\DropShip
{
    public function create($supplier, $order, $items, $settings = [])
    {
        //raise exception immediately if supplier email is not set
        if (!isset($settings['skip_notification']))
        {
            if ($supplier->getsup_enable_notification() && !$supplier->getsup_email())
                throw new \Exception('No email configured for this supplier');
        }

        if($this->_config->invoiceOrder() && $this->_config->partialInvoiceOrder()){
            $this->_invoice->createPartialInvoice($order->getId(),$items);
        }
        else if($this->_config->invoiceOrder() && (!$this->_config->partialInvoiceOrder())){
            $this->_invoice->createFullInvoice($order->getId());
        }

        $warehouseId = null;
        if (isset($settings['force_warehouse']))
            $warehouseId = $settings['force_warehouse'];
        else
        {
            foreach($items as $item)
            {
                $extendedOrderItem = $this->_extendedOrderItemFactory->create()->loadByItemId($item['order_item']->getitem_id());
                $warehouseId = $extendedOrderItem->getesfoi_warehouse_id();
            }
        }

        $po = $this->createPo($order, $supplier, $warehouseId, $settings);

        foreach($items as $item) {
            #$additionnal = ['pop_price' => $item['price'], 'pop_price_base' => $item['price'], 'pop_dropship_order_item_id' => $item['order_item_id']];
			$additionnal = ['pop_price' => $item['price'], 'pop_price_base' => $item['price'], 'pop_dropship_order_item_id' => $item['order_item_id'], 'qty_pack' => ''];
            $po->addProduct($item['order_item']->getproduct_id(), $item['qty'], $additionnal);

            /*Save supplier shipping cose from order item*/
            $po->setData('po_shipping_cost',$item['order_item']->getData('supplier_rate'));
            $po->setData('po_shipping_cost_base',$item['order_item']->getData('supplier_rate'));
            $po->save();
        }

        $po->updateTotals();

        $this->_eventManager->dispatch('bms_dropship_after_dropship_created', ['po' => $po]);

        if (!isset($settings['skip_notification'])) {
            if (!$this->_config->internalValidationEnabled()) {
                if ($supplier->getsup_enable_notification())
                    $this->notifySupplier($po);
            }
        }

        return $po;
    }
}
