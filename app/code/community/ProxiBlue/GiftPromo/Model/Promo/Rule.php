<?php

/**
 * Promo rule rule model
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Promo_Rule extends Mage_Rule_Model_Rule
{ //Mage_Rule_Model_Abstract { // v12 introduced abtract class


    /**
     * Coupon types
     */

    const COUPON_TYPE_NO_COUPON = 1;
    const COUPON_TYPE_SPECIFIC = 2;
    const COUPON_TYPE_AUTO = 3;

    /**
     * Store coupon code generator instance
     *
     * @var ProxiBlue_GiftPromo_Model_Promo_Coupon_CodegeneratorInterface
     */
    protected static $_couponCodeGenerator;

    /**
     * Rule's primary coupon
     *
     * @var ProxiBlue_GiftPromo_Model_Promo_Coupon
     */
    protected $_primaryCoupon;

    /**
     * Rule's subordinate coupons
     *
     * @var array of ProxiBlue_GiftPromo_Model_Promo_Coupon
     */
    protected $_coupons;

    /**
     * Coupon types cache for lazy getter
     *
     * @var array
     */
    protected $_couponTypes;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'giftpromo_promo_rule';
    protected $_itemCollection;
    protected $_skipSelectedGift = false;

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getGiftProdyctsRule() in this case
     *
     * @var string
     */
    protected $_eventObject = 'giftProductsPromoRule';

    /**
     * Internal holder for helper class
     *
     * @var object
     */
    private $_helper;

    /**
     * Returns code mass generator instance for auto generated specific coupons
     *
     * @return Mage_SalesRule_Model_Coupon_MassgneratorInterface
     */
    public static function getCouponMassGenerator()
    {
        return Mage::getSingleton('giftpromo/promo_coupon_massgenerator');
    }

    /**
     * Get rule condition combine model instance
     *
     */
    public function getConditionsInstance()
    {
        $conditionsModel = Mage::getModel('giftpromo/promo_rule_condition_combine');

        return $conditionsModel;
    }

    public function getActionsInstance()
    {
        return 'No Actions'; // no actions in this rule
    }

    /**
     * Validate rule conditions to determine if rule can run
     *
     * @param Varien_Object $object
     *
     * @return bool
     */
    public function validate(Varien_Object $object)
    {

        mage::helper('giftpromo')->debug(__FUNCTION__ . " in " . __FILE__ . " at " . __LINE__, 10);

        // first determine if rule can be used, or is limited by usage per order
        if ($this->getUsesPerRule() > 0 && !$this->getAllowGiftSelection()) {
            $giftCartItems = $this->getGiftCartItems($object);
            $giftCartItemUsage = count($giftCartItems);
            if ($giftCartItemUsage >= ($this->getUsesPerRule() + 1)) {
                // flag to prevent all items removal in calling logic
                $this->setNotValidDueToUsageLimit(true);

                return false;
            }
        }

        return $this->getConditions()->validate($object);
    }

    /**
     * Find Gift allocated to rule in cart
     *
     * @return array
     */
    public function getGiftCartItems($quote)
    {
        $findGiftItems = array();
        $allVisibleItems = $this->_getHelper()->getAllVisibleItems($quote);
        foreach ($allVisibleItems as $findGiftItem) {
            if ($findGiftItem->getGiftingRule() == $this->getRuleId()) {
                $findGiftItems[$findGiftItem->getProductId()] = $findGiftItem;
            }
            if (!$this->_getHelper()->testGiftTypeCode($findGiftItem->getProductType())) {
                continue;
            }
            if ($this->getRuleOfGift($findGiftItem) == $this->getRuleId()) {
                $findGiftItems[$findGiftItem->getProductId()] = $findGiftItem;
            }
        }

        return $findGiftItems;
    }

    /**
     * Get the rule id of given item
     *
     * @param ProxiBlue_GiftPromo_Model_Sales_Quote_Item $item
     *
     * @return \Mage_Catalog_Model_Product|\Varien_Object
     */
    public function getRuleOfGift($item)
    {
        if ($buyRequest = Mage::Helper('giftpromo')->isAddedAsGift($item)) {
            return $buyRequest->getAddedByRule();
        }
    }

    /**
     * Validate rule conditions to determine if rule can run
     *
     * @param Varien_Object $object
     *
     * @return bool
     */
    public function validateCount(Varien_Object $object)
    {
        return $this->getConditions()->validateCount($object);
    }

    /**
     * Check if a flag is active on any rules
     *
     * @param string $flag
     *
     * @return bool
     */
    public function getFlag($flag)
    {
        return $this->getConditions()->getFlag($flag);
    }

    /**
     * Get items collection
     *
     * @return type
     */
    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     * Get items as an array
     *
     * @return type
     */
    public function getItemsArray($allowInject = true, $latestItem = false)
    {
        if ($this->getInjectedGifts()) {
            return $this->getInjectedGifts();
        }

        if ($this->getOverrideItems()) {
            return $this->getOverrideItems();
        }

        $items = $this->_itemCollection->getItems();

        if ($allowInject && $this->getGiftAddedProduct()) {
            if (!$latestItem) {
                $currentGiftCartItems = $this->_getHelper()->getAllGiftBasedCartItems();
                $itemList = array();
                foreach ($currentGiftCartItems as $item) {
                    if ($parentQuoteId = $this->_getHelper()->getParentQuoteItemOfGift($item, true)) {
                        $itemList[] = $parentQuoteId;
                    }
                }
                $collection = Mage::getModel('checkout/session')->getQuote()->getItemsCollection();
                if (count($itemList) > 0) {
                    $collection->addFieldToFilter('item_id', array('nin' => $itemList));
                }
                $collection->addFieldToFilter('product_type', array('nlike' => 'gift-%'));
                $collection->addFieldToFilter('parent_item_id', array('null' => true));
                $collection->getSelect()->order('created_at DESC');
                foreach ($collection as $cartItem) {
                    if (is_object($cartItem) && $cartItem->getId()
                        && $this->_getHelper()->testGiftTypeCode(
                            $cartItem->getTypeId()
                        ) == false
                    ) {
                        $this->injectGiftProduct($cartItem, false, false);
                        $injected = $this->getInjectedGifts();
                        $giftItem = array_pop($injected);
                        $giftItem->setIsInjectedGift('x_x');
                        Mage::register('is_injecting_x_x', true, true);
                        $items[$giftItem->getId()] = $giftItem;
                        $this->setOverrideItems($items);
                        $this->unsInjectedGifts();
                    }
                }
            } else {
                $this->injectGiftProduct($latestItem, false, false);
                $injected = $this->getInjectedGifts();
                $giftItem = array_pop($injected);
                $items[$giftItem->getId()] = $giftItem;
                $this->unsInjectedGifts();
            }
        }

        return $items;
    }

    /**
     * Inject the given item to the available list of gift items
     *
     * @param $item
     */
    public function injectGiftProduct($item, $skipTriggerItem = true, $canDelete = true, $forcedQty = 0)
    {
        $giftProductModel = Mage::getModel('giftpromo/product')->cloneProduct(
            $item->getProduct()
        );
        $giftProductModel
            ->setTypeId(
                mage::Helper('giftpromo')
                    ->getGiftProductType($item->getProduct()->getTypeId())
            );
        $newData = array(
            'gifted_price' => $this->getCheapestPriceFromRule(),
            'gifted_message' => $this->getRuleName(),
            'gifted_label' => $this->_getHelper()->__('Your promotional item'),
            'skip_trigger_item' => $skipTriggerItem,
            'gift_rule_id' => $this->getId(),
            'gift_parent_item' => $item,
            'gift_can_delete' => $canDelete
        );
        if ($forcedQty > 0) {
            $newData['qty'] = $forcedQty;
        }
        switch ($item->getProduct()->getTypeId()) {
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
                if (is_object($infoBuyRequest)) {
                    $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
                    if (is_object($buyRequest)) {
                        $giftProductModel->setSuperAttribute($buyRequest->getSuperAttribute());
                    }
                }
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
                if (is_object($infoBuyRequest)) {
                    $buyRequest = new Varien_Object(unserialize($infoBuyRequest->getValue()));
                    if (is_object($buyRequest)) {
                        $giftProductModel->setBundleOption($buyRequest->getBundleOption());
                        $giftProductModel->setBundleOptionQty($buyRequest->getBundleOptionQty());
                    }
                }
                break;
        }
        $newData = array_merge($giftProductModel->getData(), $newData);
        $giftProductModel->setData($newData);
        $currentInjectedGifts = $this->getInjectedGifts();
        if (!is_array($currentInjectedGifts)) {
            $currentInjectedGifts = array();
        }
        $currentInjectedGifts[] = $giftProductModel;
        $this->setInjectedGifts($currentInjectedGifts);
    }

    /**
     * Get the collection, with filters enabled
     *
     * @return Object
     */
    public function getCollection()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $store = Mage::getSingleton('adminhtml/session_quote')->getStore();
            $customer = Mage::getSingleton('adminhtml/session_quote')->getCustomer();
            $groupId = $customer->getGroupId();
        } else {
            $quote = Mage::getModel('checkout/session')->getQuote();
            $store = Mage::app()->getStore($quote->getStoreId());
            $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        }
        $collection = $this->getResourceCollection();
        $collection->enableDateFilter();
        $collection->addIsActiveFilter();
        $collection->addWebsiteFilter($store->getWebsiteId());
        $collection->addCustomerFilter($groupId);

        return $collection;
    }

    /**
     * Used by select Gifts to skip the current selected gift from being displayed
     */
    public function setSkipSelectedGift(
        $skip
    )
    {
        $this->_skipSelectedGift = $skip;

        return $this;
    }

    /**
     * Retrieve coupon types
     *
     * @return array
     */
    public function getCouponTypes()
    {
        if ($this->_couponTypes === null) {
            $this->_couponTypes = array(
                Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON => Mage::helper('salesrule')->__('No Coupon'),
                Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC => Mage::helper('salesrule')->__('Specific Coupon'),
            );
            $transport = new Varien_Object(
                array(
                    'coupon_types' => $this->_couponTypes,
                    'is_coupon_type_auto_visible' => false
                )
            );
            Mage::dispatchEvent('giftpromo_prono_rule_get_coupon_types', array('transport' => $transport));
            $this->_couponTypes = $transport->getCouponTypes();
            if ($transport->getIsCouponTypeAutoVisible()) {
                $this->_couponTypes[Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO] = Mage::helper('salesrule')->__(
                    'Auto'
                );
            }
        }

        return $this->_couponTypes;
    }

    /**
     * Acquire coupon instance
     *
     * @param bool $saveNewlyCreated Whether or not to save newly created coupon
     * @param int $saveAttemptCount Number of attempts to save newly created coupon
     *
     * @return Mage_SalesRule_Model_Coupon|null
     * @throws Exception
     */
    public function acquireCoupon($saveNewlyCreated = true, $saveAttemptCount = 10)
    {
        if ($this->getCouponType() == self::COUPON_TYPE_NO_COUPON) {
            return null;
        }
        if ($this->getCouponType() == self::COUPON_TYPE_SPECIFIC) {
            return $this->getPrimaryCoupon();
        }
        /** @var Mage_SalesRule_Model_Coupon $coupon */
        $coupon = Mage::getModel('giftpromo/promo_coupon');
        $coupon->setRule($this)
            ->setIsPrimary(false)
            ->setUsageLimit($this->getUsesPerCoupon() ? $this->getUsesPerCoupon() : null)
            ->setUsagePerCustomer($this->getUsesPerCustomer() ? $this->getUsesPerCustomer() : null)
            ->setExpirationDate($this->getToDate());

        $couponCode = self::getCouponCodeGenerator()->generateCode();
        $coupon->setCode($couponCode);

        $ok = false;
        if (!$saveNewlyCreated) {
            $ok = true;
        } else {
            if ($this->getId()) {
                for ($attemptNum = 0; $attemptNum < $saveAttemptCount; $attemptNum++) {
                    try {
                        $coupon->save();
                    } catch (Exception $e) {
                        if ($e instanceof Mage_Core_Exception || $coupon->getId()) {
                            throw $e;
                        }
                        $coupon->setCode(
                            $couponCode .
                            self::getCouponCodeGenerator()->getDelimiter() .
                            sprintf('%04u', rand(0, 9999))
                        );
                        continue;
                    }
                    $ok = true;
                    break;
                }
            }
        }
        if (!$ok) {
            Mage::throwException(Mage::helper('salesrule')->__('Can\'t acquire coupon.'));
        }

        return $coupon;
    }

    /**
     * Returns code generator instance for auto generated coupons
     *
     * @return Mage_SalesRule_Model_Coupon_CodegeneratorInterface
     */
    public static function getCouponCodeGenerator()
    {
        if (!self::$_couponCodeGenerator) {
            return Mage::getSingleton('giftpromo/promo_coupon_codegenerator', array('length' => 16));
        }

        return self::$_couponCodeGenerator;
    }

    /**
     * Set resource model and Id field name
     */
    protected function _construct()
    {
        $this->_init('giftpromo/promo_rule');
        $this->setIdFieldName('rule_id');
    }

    /**
     * Set coupon code and uses per coupon
     *
     * @return Mage_SalesRule_Model_Rule
     */
    protected function _afterLoad()
    {
        Mage::register('current_rule_object', $this, true);
        $currentSelectedGifts = Mage::helper('giftpromo')->getCurrentSelectedGifts(true);
        // format the selected products data
        $products = array();
        parse_str($this->getGiftpromo(), $products);
        /**
         * Performance improvement - eliminate model load in loop!
         * First get an array of all the product keys.
         * Then load a collection of all those products,
         * iterate that collection and populate from data fetched in the data array
         */
        $giftProductCollection = Mage::getModel('giftpromo/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', array('in'=> array_keys($products)));
        /**
         * eliminate sorting after initial iteration, removing the secondary iteration after sorting
         * also eliminates the sorting call itself.
         * This required a new ProxiBlue_GiftPromo_Varien_Data_Collection object (derived from Varien_Data_Collection)
         * but with the ability to sort items, or add them into sorted item slots in the collections
         *
         **/
        $this->_itemCollection = new ProxiBlue_GiftPromo_Varien_Data_Collection;
        foreach($giftProductCollection as $giftProductModel) {
            $key = $giftProductModel->getId();
            if (!Mage::app()->getStore()->isAdmin() && $this->_skipSelectedGift
                && $selectedKey = array_search($this->getRuleId(), $currentSelectedGifts) !== false
            ) {
                $keyParts = explode('_', $currentSelectedGifts[$selectedKey]);
                $theKey = array_pop($keyParts);
                if ($key == $theKey) {
                    unset($products[$key]);
                    continue;
                }

            }
            $lineData = $this->getLineData($products[$key]);
            $giftProductModel->setTypeId($this->_getHelper()->getGiftProductType($giftProductModel->getTypeId()));
            $newData = array_merge($giftProductModel->getData(), $lineData);
            $giftProductModel->setData($newData);
            $products[$key] = $lineData;
            $giftProductModel->setGiftRuleId($this->getId());
            $products[$key]['product_object'] = $giftProductModel;
            try {
                $this->_itemCollection->addItem($giftProductModel);
            } catch (Exception $e) {
                // fail silently, as the item already exists as a gift, thus don't add it again
            }
        }
        $this->_itemCollection->sortItems();
        $this->setGiftedProducts($products);
        unset($products);
        
        // format the website_ids to an array

        if ($this->getWebsiteIds() && !is_array($this->getWebsiteIds())) {
            $this->setWebsiteIds(explode(',', $this->getWebsiteIds()));
        }

        //format the customer_ids to an array

        if ($this->getCustomerIds() && !is_array($this->getCustomerIds())) {
            $this->setCustomerIds(explode(',', $this->getCustomerIds()));
        }

        if ($this->getPrimaryCoupon()->getType()) {
            // we have a valid coupon type
            $this->setCouponCode($this->getPrimaryCoupon()->getCode());
            $this->setCouponType($this->getPrimaryCoupon()->getType());
            if ($this->getUsesPerCoupon() !== null && !$this->getUseAutoGeneration()) {
                $this->setUsesPerCoupon($this->getPrimaryCoupon()->getUsageLimit());
            }
        } else {
            if (is_null($this->getCouponType() || $this->getCouponType() === false)) {
                $this->setCouponType(self::COUPON_TYPE_NO_COUPON);
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Convert the line data from string to array with element keys
     *
     * @param string $lineData
     *
     * @return array
     */
    private function getLineData($lineData)
    {
        $lineData = explode('|', $lineData);
        $linkInfo = array(
            'gifted_price' => $lineData[0],
            'gifted_message' => (string)$lineData[1],
            'gifted_label' => (string)$lineData[2],
        );
        if (array_key_exists('3', $lineData)) {
            $linkInfo['gifted_position'] = (int)$lineData[3];
        } else {
            $linkInfo['gifted_position'] = 0;
        }
        if (array_key_exists('4', $lineData)) {
            $linkInfo['gifted_qty_max'] = (int)$lineData[4];
        } else {
            $linkInfo['gifted_qty_max'] = 0;
        }
        if (array_key_exists('5', $lineData)) {
            $linkInfo['gifted_qty_rate'] = $lineData[5];
            $data = explode(':', $linkInfo['gifted_qty_rate']);
            if (!is_array($data) || count($data) != 3) {
                $linkInfo['gifted_rate_product_qty_sku'] = '';
                $linkInfo['gifted_rate_product_qty'] = 1;
                $linkInfo['gifted_rate_gift_rate'] = 1;
            } else {
                $linkInfo['gifted_rate_product_qty_sku'] = $data[0];
                $linkInfo['gifted_rate_product_qty'] = $data[1];
                $linkInfo['gifted_rate_gift_rate'] = $data[2];
            }
        } else {
            $linkInfo['gifted_qty_rate'] = '1:1';
            $linkInfo['gifted_rate_product_qty_sku'] = '';
            $linkInfo['gifted_rate_product_qty'] = 1;
            $linkInfo['gifted_rate_gift_rate'] = 1;
        }

        return $linkInfo;
    }

    /**
     * Return the gifted products as product objects in an array
     *
     * @return array
     */
    public function getGiftedAsProducts()
    {
        return $this->_itemCollection->getItems();
    }

    /**
     * Get the helper class and cache teh object
     *
     * @return object
     */
    private function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::Helper('giftpromo');
        }

        return $this->_helper;
    }

    /**
     * Get rule associated website Ids
     *
     * @return array
     */
    public
    function getWebsiteIds()
    {
        return $this->_getData('website_ids');
    }

    /**
     * Get rule associated customer group Ids
     *
     * @return array
     */
    public function getCustomerIds()
    {
        return $this->_getData('customer_ids');
    }

    /**
     * Retrieve rule's primary coupon
     *
     * @return Mage_SalesRule_Model_Coupon
     */
    public function getPrimaryCoupon()
    {
        if ($this->_primaryCoupon === null) {
            $this->_primaryCoupon = Mage::getModel('giftpromo/promo_coupon');
            $this->_primaryCoupon->loadPrimaryByRule($this->getId());
            $this->_primaryCoupon->setGiftPromoRule($this)->setIsPrimary(true);
        }

        return $this->_primaryCoupon;
    }

    /**
     * Save/delete coupon
     *
     * @return Mage_SalesRule_Model_Rule
     */
    protected function _afterSave()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $couponCode = trim($this->getCouponCode());
            if (strlen($couponCode) && $this->getCouponType() == self::COUPON_TYPE_SPECIFIC
                && !$this->getUseAutoGeneration()
            ) {
                $this->getPrimaryCoupon()
                    ->setCode($couponCode)
                    ->setUsageLimit($this->getUsesPerCoupon() ? $this->getUsesPerCoupon() : null)
                    ->setUsagePerCustomer($this->getCouponUsesPerCustomer() ? $this->getCouponUsesPerCustomer() : null)
                    ->setExpirationDate($this->getToDate())
                    ->setType($this->getCouponType())
                    ->save();
            } else {
                $this->getPrimaryCoupon()->delete();
            }
            if ($this->getCouponType() == self::COUPON_TYPE_SPECIFIC) {
                $coupons = $this->getCoupons();
                // update the generated coupons usage values.
                // optimize to one sql, not this foreach.
                foreach ($coupons as $coupon) {
                    $coupon->setUsageLimit($this->getUsesPerCoupon() ? $this->getUsesPerCoupon() : 0);
                    $coupon->setUsagePerCustomer(
                        $this->getCouponUsesPerCustomer() ? $this->getCouponUsesPerCustomer() : 0
                    );
                    $coupon->save();
                }
            }
        }

        return parent::_afterSave();
    }

    /**
     * Retrieve subordinate coupons
     *
     * @return array of Mage_SalesRule_Model_Coupon
     */
    public function getCoupons()
    {
        if ($this->_coupons === null) {
            $collection = Mage::getResourceModel('giftpromo/promo_coupon_collection');
            /** @var Mage_SalesRule_Model_Resource_Coupon_Collection */
            $collection->addRuleToFilter($this);
            $this->_coupons = $collection->getItems();
        }

        return $this->_coupons;
    }

    /**
     * Prepare data before saving
     *
     * @return Mage_Rule_Model_Abstract
     */
    protected function _beforeSave()
    {
        // Serialize conditions
        if ($this->getConditions()) {
            $this->setConditionsSerialized(serialize($this->getConditions()->asArray()));
            $this->unsConditions();
        }
        if (is_array($this->getWebsiteIds())) {
            $this->setWebsiteIds(implode(',', $this->getWebsiteIds()));
        }
        if (is_array($this->getCustomerIds())) {
            $this->setCustomerIds(implode(',', $this->getCustomerIds()));
        }
        $this->unsGiftedProducts();

        return $this;
    }

    /**
     * Adjust to remove all blocked rules products from cart, since they should not be processing now
     *
     * @param array $rules All the rules that was gonna process
     * @param       object The current Quote Object
     *
     * @return mixed
     */
    public function getStopRulesProcessing($rules, $quote)
    {
        if ($this->getData('stop_rules_processing')) {
            if ($this->getAllowGiftSelection()) {
                $currentSelectedGifts = $this->_getHelper()->getCurrentSelectedGifts(true);
                if (count($currentSelectedGifts) == 0) {
                    return false;
                }
                if (!array_search($this->getId(), $currentSelectedGifts)) {
                    return false;
                }
            }
            if ($this->getData('keep_validated_on_stop') == 0) {
                $this->effectRemoveOfLesserGifts($rules, $quote);
            }

            return true;
        }

        return false;
    }

    private function effectRemoveOfLesserGifts($rules, $quote)
    {
        $cartGifts = Mage::helper('giftpromo')->getRuleBasedCartItems();
        foreach ($rules as $rule) {
            if ($rule->getSortOrder() < $this->getSortOrder()
                && array_key_exists($rule->getId(), $cartGifts)
            ) {
                $giftsToRemove = $cartGifts[$rule->getId()];
                foreach ($giftsToRemove as $remove) {
                    $quote->removeItem($remove->getId());
                }
                unset($cartGifts[$rule->getId()]);
            }
        }
    }


    public function getAllowGiftSelectionCount()
    {
        if ($this->getData('allow_gift_selection_count') == -1) {
            $object = Mage::getSingleton('checkout/session')->getQuote();
            if (is_object($object)) {
                $totalQty = 0;
                // tally up the qty for all non gift items
                $allVisibleItems = mage::helper('giftpromo')->getAllVisibleItems($object);
                foreach ($allVisibleItems as $item) {
                    if (!mage::helper('giftpromo')->testGiftTypeCode($item->getProductType())) {
                        $totalQty++;
                    }
                }
                $this->setData('allow_gift_selection_count', $totalQty);
            }
        }

        return $this->getData('allow_gift_selection_count');
    }

    public function getCheapestPriceFromRule()
    {
        foreach ($this->getConditions()->getConditions() as $cond) {
            $conditionObject = mage::getModel($cond['type']);
            if ($conditionObject instanceof ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Subselect) {
                return $cond->getDiscountValue();
            }
        }
        return 0;
    }

    public function hasCheapestItemRule()
    {
        foreach ($this->getConditions()->getConditions() as $cond) {
            $conditionObject = mage::getModel($cond['type']);
            if ($conditionObject instanceof ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Product_Subselect_Free) {
                return true;
            }
        }
    }
}
