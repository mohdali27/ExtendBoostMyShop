<?php
namespace Webappmate\ExtendBoostMyShop\Controller\Adminhtml\Supplier;
use Magento\Framework\App\ResponseInterface;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportTablerates extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \BoostMyShop\Supplier\Model\SupplierFactory
     */
    protected $_supplierFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker $sectionChecker
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \BoostMyShop\Supplier\Model\SupplierFactory $supplierFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_fileFactory = $fileFactory;
        $this->_supplierFactory = $supplierFactory;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    /**
     * Export shipping table rates in csv format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        
        /** @var $gridBlock \Webappmate\ExtendBoostMyShop\Block\Adminhtml\Carrier\Tablerate\Grid */
        $gridBlock = $this->_view->getLayout()->createBlock(
            \Webappmate\ExtendBoostMyShop\Block\Adminhtml\Carrier\Tablerate\Grid::class
        );
        $website = $this->getRequest()->getParam('website');
        $supId = $this->getRequest()->getParam('id');
        $supplier = $this->_supplierFactory->create()->load($supId);
        $conditionName = $supplier->getData('tablerate_condition');
        //if there is no condition for already created supplier then set default

        if(!$conditionName){
            $conditionName = 'package_value';
        }
        $gridBlock->setWebsiteId($website)->setConditionName($conditionName)
            ->setSupplier($supId);
        $content = $gridBlock->getCsvFile();
        $fileName = 'tablerates_'.$supId.'.csv';
        return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
