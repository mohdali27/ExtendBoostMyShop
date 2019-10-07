<?php
namespace Webappmate\ExtendBoostMyShop\Block\Main\Tab\Renderer\PendingShipping;

use Magento\Framework\DataObject;

class Misc extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    public function render(DataObject $order)
    {
        $shippingCost = $order->getpo_shipping_cost() > 0 ? number_format($order->getpo_shipping_cost(),2) : '';
        $editable =  $order->getpo_shipping_cost() > 0 ? 'readonly="readonly"' : '';
        $html = [];
        $html[] = '<table border="0" width="100%" id="table_pendingshipping_misc_'.$order->getId().'">';

        $html[] = '<tr>';
        $html[] = '<td>'.__('Tracking #').'</td>';
        $html[] = '<td><input type="textbox" id="tracking_'.$order->getId().'" value="'.$order->getpo_shipping_tracking().'" name="poMisc['.$order->getId().'][tracking]"></td>';
        $html[] = '</tr>';

        $html[] = '<tr>';
        $html[] = '<td>'.__('Shipping cost').'</td>';
        $html[] = '<td><input type="textbox" id="shipping_'.$order->getId().'" name="poMisc['.$order->getId().'][shipping]" value="'.$shippingCost.'" " '.$editable.'"></td>';
        $html[] = '</tr>';

        $html[] = '<tr>';
        $html[] = '<td>'.__('Notify customer').'</td>';
        $html[] = '<td><select id="notify_'.$order->getId().'" name="poMisc['.$order->getId().'][notify]" id="">';
        $html[] = '<option value="1">'.__('Yes').'</option>';
        $html[] = '<option value="0">'.__('No').'</option>';
        $html[] = '</select></td>';
        $html[] = '</tr>';

        $html[] = '</table>';

        return implode('', $html);
    }
}
