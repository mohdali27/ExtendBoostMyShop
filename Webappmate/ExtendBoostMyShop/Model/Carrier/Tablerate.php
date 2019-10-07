<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Webappmate\ExtendBoostMyShop\Model\Carrier;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Table rate shipping model
 *
 * @api
 * @since 100.0.2
 */
class Tablerate extends \Magento\OfflineShipping\Model\Carrier\Tablerate
{
    
    /**
     * @var \BoostMyShop\DropShip\Model\DropShipper
     */
    protected $_dropShipper;
    
    /**
     * @var \Webappmate\ExtendBoostMyShop\Model\ResourceModel\Tablerate
     */
    protected $_supplierRateFactory;


    /**
     * @var \BoostMyShop\DropShip\Model\Config
     */
    protected $_config;

    /**
     * @var string
     */
    static $defaultCondition = 'package_value';



    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $resultMethodFactory
     * @param \Magento\OfflineShipping\Model\ResourceModel\Carrier\TablerateFactory $tablerateFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $resultMethodFactory,
        \Magento\OfflineShipping\Model\ResourceModel\Carrier\TablerateFactory $tablerateFactory,
        \BoostMyShop\DropShip\Model\DropShipperFactory $dropShipper,
        \BoostMyShop\DropShip\Model\Config $config,
        \Webappmate\ExtendBoostMyShop\Model\ResourceModel\TablerateFactory $supplierRateFactory,
        array $data = []
    ) {
        $this->_dropShipper = $dropShipper;
        $this->_config = $config;
        $this->_supplierRateFactory = $supplierRateFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger,$rateResultFactory,$resultMethodFactory,$tablerateFactory,$data);
    }
    

    
    
    /**
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function collectRates(RateRequest $request)
    {

        if (!$this->getConfigFlag('active')) {
            return false;
        }
        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
        }

        // Free shipping by qty
        $freeQty = 0;
        if ($request->getAllItems()) {
            $freePackageValue = 0;
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeShipping = is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0;
                            $freeQty += $item->getQty() * ($child->getQty() - $freeShipping);
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeShipping = is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0;
                    $freeQty += $item->getQty() - $freeShipping;
                    $freePackageValue += $item->getBaseRowTotal();
                }
            }
            $oldValue = $request->getPackageValue();
            $request->setPackageValue($oldValue - $freePackageValue);
        }

        if (!$request->getConditionName()) {
            $conditionName = $this->getConfigData('condition_name');
            $request->setConditionName($conditionName ? $conditionName : $this->_defaultConditionName);
        }
        
        // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();
        $request->setPackageWeight($request->getFreeMethodWeight());
        $request->setPackageQty($oldQty - $freeQty);

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();
        $rate = $this->getRate($request);
        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);

//Get suppliers and items
$supRateRequest = $request;
$supRates = $this->getSupplierItems($supRateRequest);
$totalRate = 0;
foreach ($supRates as $itemRate) {
   $totalRate = $totalRate + $itemRate['sup_rate'];
}

//save supplier table rate for each item in quote

/*
 create global configuration to include global shipping rate yes/no
 if(yes) {
    $rate['price'] = $rate['price'] + $totalRate;
 } else {
    $rate['price'] = $rate['price'];
 }
*/
$rate['price'] = $totalRate;
//echo"<pre>$totalRate";print_r($supRates);die;


//
        if (!empty($rate) && $rate['price'] >= 0) {
            if ($request->getPackageQty() == $freeQty) {
                $shippingPrice = 0;
            } else {
                $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);
            }
            //set custom default shipping rate
            //$shippingPrice = 53;


            $method = $this->createShippingMethod($shippingPrice, $rate['cost']);
            $result->append($method);
        } elseif ($request->getPackageQty() == $freeQty) {

            /**
             * Promotion rule was applied for the whole cart.
             *  In this case all other shipping methods could be omitted
             * Table rate shipping method with 0$ price must be shown if grand total is more than minimal value.
             * Free package weight has been already taken into account.
             */
            $request->setPackageValue($freePackageValue);
            $request->setPackageQty($freeQty);
            $rate = $this->getRate($request);
            if (!empty($rate) && $rate['price'] >= 0) {
                $method = $this->createShippingMethod(0, 0);
                $result->append($method);
            }
        } else {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Error $error */
            $error = $this->_rateErrorFactory->create(
                [
                    'data' => [
                        'carrier' => $this->_code,
                        'carrier_title' => $this->getConfigData('title'),
                        'error_message' => $this->getConfigData('specificerrmsg'),
                    ],
                ]
            );
            $result->append($error);
        }

        return $result;
    }

    /**
     * @param RateRequest $request
     * @return array $resultItems
     */
    public function getSupplierItems(RateRequest $request)
    {
        $supRate = [];
        $items = $request->getAllItems();
        foreach ($items as $key=>$item) {
            $supRate[$key]['item_id'] = $item->getItemId();
            $productId = $item->getproduct_id();
            $productSuppliers = $this->_dropShipper->create()->getSuppliersForProduct($productId);
            if($productSuppliers->getSize() > 0) {

                //get default supplier of a item as per the dropshipping rules
                $defaultSupplier = $this->getDefaultSupplier($productSuppliers);

                //Get supplier condition name
                $conditionName = $defaultSupplier->getData('tablerate_condition')? $defaultSupplier->getData('tablerate_condition') : self::$defaultCondition;
                $package_value = $item->getRowTotal();
                $package_weight = $item->getRowWeight();
                $package_qty = $item->getQty();

                //echo "<pre>";print_r(json_encode($item->getData()));
                //echo "<pre>";print_r(json_encode($defaultSupplier->getData()));
                $supId = $defaultSupplier->getSpSupId();
                $request->setConditionName($conditionName);
                $request->setPackageValue($package_value);
                $request->setPackageWeight($package_weight);
                $request->setPackageQty($package_qty);
                $request->setSupId($supId);
                $rate = $this->getSupRate($request);
                $supRate[$key]['sup_id'] = $supId;
                if($rate){
                    $supRate[$key]['sup_rate'] = isset($rate['price'])?$rate['price']:0;
                } else {
                    $supRate[$key]['sup_rate'] = 0;
                }
                
                //echo "<pre>";print_r($rate);
            } else {
                // If no supplier associated then set default as price 0 or get global table rate from magento configuration
                $supRate[$key]['sup_rate'] = 0;
                $supRate[$key]['sup_id'] = 0;
            }
            
            //save sup_rate into quote item
            $item->setSupplierRate($supRate[$key]['sup_rate'])->save();
            //echo get_class($item);die;
        }

        //echo "<pre>";print_r($supRate);die;
        return $supRate;
    }


    public function getDefaultSupplier($collection)
    {
        $cheapestItem = null;

        foreach($collection as $item)
        {
            if ($this->_config->priorizePrimary() && $item->getsp_primary())
            {
                $item->setis_default(1);
                return $item;
            }

            if ($this->_config->priorizeCheapest())
            {
                $cheapestPrice = ($cheapestItem ? $cheapestItem->getsp_price() : 99999);
                if ($item->getsp_price() < $cheapestPrice)
                    $cheapestItem = $item;
            }
        }

        if ($cheapestItem)
            $cheapestItem->setis_default(1);
        return $cheapestItem;
    }


    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return array|bool
     */
    public function getRate(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        //echo get_class($this->_tablerateFactory->create()); die('getRate');
        return $this->_tablerateFactory->create()->getRate($request);
    }


    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return array|bool
     */
    public function getSupRate(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        //echo get_class($this->_tablerateFactory->create()); die('getRate');
        return $this->_supplierRateFactory->create()->getRate($request);
    }

    /**
     * @param string $type
     * @param string $code
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCode($type, $code = '')
    {
        $codes = [
            'condition_name' => [
                'package_weight' => __('Weight vs. Destination'),
                'package_value' => __('Price vs. Destination'),
                'package_qty' => __('# of Items vs. Destination'),
            ],
            'condition_name_short' => [
                'package_weight' => __('Weight (and above)'),
                'package_value' => __('Order Subtotal (and above)'),
                'package_qty' => __('# of Items (and above)'),
            ],
        ];

        if (!isset($codes[$type])) {
            throw new LocalizedException(__('Please correct Table Rate code type: %1.', $type));
        }

        if ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            throw new LocalizedException(__('Please correct Table Rate code for type %1: %2.', $type, $code));
        }

        return $codes[$type][$code];
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['bestway' => $this->getConfigData('name')];
    }

    /**
     * Get the method object based on the shipping price and cost
     *
     * @param float $shippingPrice
     * @param float $cost
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    private function createShippingMethod($shippingPrice, $cost)
    {
        /** @var  \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->_resultMethodFactory->create();

        $method->setCarrier('tablerate');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('bestway');
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice($shippingPrice);
        $method->setCost($cost);
        return $method;
    }
    
}
