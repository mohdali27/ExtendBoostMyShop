<?php

namespace Webappmate\ExtendBoostMyShop\Block\Supplier\Edit\Tabs;

class Tablerate extends \Magento\Backend\Block\Widget\Form\Generic
{


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Locale\ListsInterface $localeLists
     * @param array $data
     * @param \Magento\OfflineShipping\Model\Config\Source $Tablerate
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form fields
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        /** @var $model \Magento\User\Model\User */
        $model = $this->_coreRegistry->registry('current_supplier');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setEnctype('multipart/form-data');
        $form->setHtmlIdPrefix('supplier_');

        $tablerate = $form->addFieldset('table_rate', ['legend' => __('Table Rates')]);

        $tablerate->addField(
            'tablerate_condition',
            'select',
            [
                'name' => 'tablerate_condition',
                'value' => $model->getData('tablerate_condition'),
                'label' => __('Condition'),
                'options' => $this->_getOptions(),
                'required' => false
            ]
        );

        $tablerate->addType(
            'csvimport',
            '\Magento\OfflineShipping\Block\Adminhtml\Form\Field\Import'
        );
        $tablerate->addField(
            'importcsv',
            'csvimport',
            [
                'name'  => 'tablerates',
                'label' => __('Import'),
                'title' => __('Import Csv'),
              
            ]
        );

        
        $tablerate->addField(
            'export_now_button',
            'button',
            [
                'label' => __('Export'),
                'value' => __('Export'),
                'onclick' => 'window.setLocation(\''.$this->getUrl('suppliertablerate/supplier/exportTablerates', ['id' => $model->getId(),'website' => $model->getData('sup_website_id')]).'\')',
                'class' => 'primary'
                
            ]
        );

        //$data = $model->getData();
        // $form->setValues($data);
       
        $this->setForm($form);
        return parent::_prepareForm();
    }


    /**
     * @return array $conditions
    */
    protected function _getOptions()
    {
        $arr = ['package_value' => __('Price vs. Destination'),'package_weight' => __('Weight vs. Destination'),'package_qty' => __('# of Items vs. Destination')];
        return $arr;
    }

}
