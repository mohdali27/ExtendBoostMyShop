<?php
namespace Webappmate\ExtendBoostMyShop\Block\Adminhtml\Order\View\Items\Renderer;

/**
 * Adminhtml sales order item renderer
 *
 * @api
 * @since 100.0.2
 */
class DefaultRenderer extends \Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer
{
 
    /**
     * @param \Magento\Framework\DataObject|Item $item
     * @param string $column
     * @param null $field
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @since 100.1.0
     */
    public function getColumnHtml(\Magento\Framework\DataObject $item, $column, $field = null)
    {

        $html = '';
        switch ($column) {
            case 'product':
                if ($this->canDisplayContainer()) {
                    $html .= '<div id="' . $this->getHtmlId() . '">';
                }
                $html .= $this->getColumnHtml($item, 'name');
                if ($this->canDisplayContainer()) {
                    $html .= '</div>';
                }
                break;
            case 'status':
                $html = $item->getStatus();
                break;
            case 'price-original':
                $html = $this->displayPriceAttribute('original_price');
                break;
            case 'tax-amount':
                $html = $this->displayPriceAttribute('tax_amount');
                break;
            case 'tax-percent':
                $html = $this->displayTaxPercent($item);
                break;
            case 'discont':
                $html = $this->displayPriceAttribute('discount_amount');
                break;
            case 'supplier_rate':
                $priceHelper = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\Pricing\Helper\Data');
                $html = $priceHelper->currency(number_format($item->getData('supplier_rate'),2), true, false);
                break;
            default:
                $html = parent::getColumnHtml($item, $column, $field);
        }
        return $html;
    }
}
