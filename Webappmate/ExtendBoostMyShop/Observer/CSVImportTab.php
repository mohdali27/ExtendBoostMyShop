<?php 
namespace Webappmate\ExtendBoostMyShop\Observer;

/**
 * Class CSVImport
 */
class CSVImportTab implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * CSVImportTab constructor.
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $supplier = $observer->getEvent()->getSupplier();
        $tabs = $observer->getEvent()->getTabs();
        $layout = $observer->getEvent()->getLayout();

        $tabs->addTab(
            'csv_import_settings',
            [
                'label' => __('Table Rates'),
                'content' => $layout->createBlock('Webappmate\ExtendBoostMyShop\Block\Supplier\Edit\Tabs\Tablerate')->setSupplier($supplier)->toHtml()
            ]
        );

        return $this;

    }

}
