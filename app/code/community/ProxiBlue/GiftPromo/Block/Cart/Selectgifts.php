<?php

/**
 * Render block to allow gift selection
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Block_Cart_Selectgifts
    extends Mage_Checkout_Block_Cart_Abstract
{

    /**
     * Rules used
     *
     * @var array
     */
    protected $_rules = array();

    /**
     * The price block
     *
     * @var array
     */
    protected $_priceBlock = array();

    /**
     * The template for price block rendering
     *
     * @var string
     */
    protected $_priceBlockDefaultTemplate = 'catalog/product/price.phtml';

    /**
     * Price block object
     *
     * @var string
     */
    protected $_block = 'catalog/product_price';


    public $_itemCollection = null;


    /**
     * Get the items in this collection
     *
     * @return object
     */
    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     *  get cart url
     *
     * @return string
     */
    public function getCartUrl()
    {
        return Mage::getUrl(
            'checkout/cart/', array(
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
                '_store' => Mage::app()->getStore()->getId()
            )
        );
    }

    /**
     *  get review url
     *
     * @return string
     */
    public function getReviewUrl()
    {
        return Mage::getUrl(
            'onepage/reviewreload/',
            array('_secure' => Mage::app()->getStore()->isCurrentlySecure(),
                  '_store' => Mage::app()->getStore()->getId()
            )
        );
    }

    /**
     * Returns product price block html
     *
     * @param Mage_Catalog_Model_Product $product
     * @param boolean $displayMinimalPrice
     * @param string $idSuffix
     *
     * @return string
     */
    public function getPriceHtml($product, $displayMinimalPrice = false, $idSuffix = '')
    {
        $type_id = $product->getTypeId();
        if (!mage::helper('giftpromo')->isPre16()) {
            if (Mage::helper('catalog')->canApplyMsrp($product)) {
                $realPriceHtml = $this->_preparePriceRenderer($type_id)
                    ->setProduct($product)
                    ->setDisplayMinimalPrice($displayMinimalPrice)
                    ->setIdSuffix($idSuffix)
                    ->toHtml();
                $product->setAddToCartUrl($this->getAddToCartUrl($product));
                $product->setRealPriceHtml($realPriceHtml);
                $type_id = $this->_mapRenderer;
            }
        }

        return $this->_preparePriceRenderer($type_id)
            ->setProduct($product)
            ->setDisplayMinimalPrice($displayMinimalPrice)
            ->setIdSuffix($idSuffix)
            ->toHtml();
    }

    /**
     * Prepares and returns block to render some product type
     *
     * @param string $productType
     *
     * @return Mage_Core_Block_Template
     */
    public function _preparePriceRenderer($productType)
    {
        return $this->_getPriceBlock($productType)
            ->setTemplate($this->_getPriceBlockTemplate($productType))
            ->setUseLinkForAsLowAs($this->_useLinkForAsLowAs);
    }

    /**
     * get the block to be used for rendering the price display
     *
     * @param object $productTypeId
     *
     * @return object
     */
    protected function _getPriceBlock($productTypeId)
    {
        if (!isset($this->_priceBlock[$productTypeId])) {
            $block = $this->_block;
            if (isset($this->_priceBlockTypes[$productTypeId])) {
                if ($this->_priceBlockTypes[$productTypeId]['block'] != '') {
                    $block = $this->_priceBlockTypes[$productTypeId]['block'];
                }
            }
            $this->_priceBlock[$productTypeId] = $this->getLayout()->createBlock($block);
        }

        return $this->_priceBlock[$productTypeId];
    }

    /**
     * Get the template to be used with price rendering for product type
     *
     * @param int $productTypeId
     *
     * @return string
     */
    protected function _getPriceBlockTemplate($productTypeId)
    {
        if (isset($this->_priceBlockTypes[$productTypeId])) {
            if ($this->_priceBlockTypes[$productTypeId]['template'] != '') {
                return $this->_priceBlockTypes[$productTypeId]['template'];
            }
        }

        return $this->_priceBlockDefaultTemplate;
    }

    /**
     * Retrive add to cart url
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getAddToCartUrl($product)
    {
        if ($product->getTypeId() == ProxiBlue_GiftPromo_Model_Product_Type_Gift_Bundle::TYPE_CODE) {
            return $product->getProductUrl();
        }

        return Mage::getUrl(
            'giftpromo/cart/addgift',
            array('_secure' => Mage::app()->getStore()->isCurrentlySecure(),
                  '_store' => Mage::app()->getStore()->getId()
            )
        );
    }

    /**
     * Return true if product has options
     *
     * @return bool
     */
    public function hasOptions($product)
    {
        if ($product->getTypeInstance(true)->hasOptions($product)) {
            return true;
        }

        return false;
    }

    /**
     * Generate a unique id for this product, and assign to product model.
     * It can then be used throughout all child blocks as well.
     * This is required to keep form and element id's unique, so the sameproduct can appear multiple times, and
     * not clash with each other.
     *
     * @param object $product
     *
     * @return string
     */
    public function addUniqueId($product)
    {
        if (is_null($product->getGiftUid())) {
            $product->setGiftUid(
                rand(
                    0,
                    10000000000
                ) . md5(
                    uniqid(
                        rand(
                            0,
                            10000000000
                        ) . '_',
                        true
                    )
                ) . rand(
                    0,
                    10000000000
                )
            );
        }

        return $product;
    }

    /**
     * Build html for options wrapper
     *
     * @param object $_item
     *
     * @return string
     */
    public function getOptionsHtml($_item)
    {
        $childBlock = $this->getChild($_item->getTypeId() . '_product_options_wrapper');
        if (is_object($childBlock)) {
            return $childBlock->setProduct($_item)->toHtml();
        }

        return '';
    }

    public function getProductOptionsHtml($_item)
    {
        $childBlock = $this->getChild('product_options_wrapper');
        if (is_object($childBlock)) {
            return $childBlock->setProduct($_item)->toHtml();
        }

        return '';
    }

    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * Determine if the select list can display
     *
     * @param $_object
     * @param $giftItemKey
     *
     * @return bool
     */
    public function canDisplay($_object, $giftItemKey)
    {
        $itemKeys = explode('_', $giftItemKey);
        $parentId = array_pop($itemKeys);
        $allowedByRatio = 0;
        $_giftItems = $this->filterItems($_object);
        //temporarily add in the items to cart so we can validate them as valid via qty ratio:
        try {
            foreach ($_giftItems as $itemKey => $_item) {
                $copyQuote = Mage::helper('giftpromo/gifticon')->testItemHasValidGifting($_item, false, true);
                $infoBuyRequest = $buyRequest = new Varien_Object(
                    array('value' => serialize(
                        array('added_by_rule' => 1,
                              'parent_quote_item_id' => $parentId)
                    ))
                );
                foreach ($copyQuote->getData('all_visible_items') as $item) {
                    $item->setCustomOptions(array('info_buyRequest' => $infoBuyRequest));
                    $item->setQuote($this->getQuote());
                    $item->setSkipSave(true);
                }
                $allowedByRatio = Mage::helper('giftpromo')->calculateQtyrate(
                    array($copyQuote->getData('all_visible_items')),
                    $this->getQuote()
                );

            }
        } catch (Exception $e) {
            //throw exception forward
            Mage::logException($e);
        }

        //$quoteGiftItems = Mage::helper('giftpromo')->getRuleBasedCartItems();

        if ($allowedByRatio > 0) {

            $_selectedCount = 0;
            foreach ($_giftItems as $itemKey => $_item) {
                //$_selectedCount = $this->isCurrentSelected($giftItemKey . '_' . $_item->getId());
                $currentSelectedGifts = Mage::helper('giftpromo')->getCurrentSelectedGifts();
                $slimIds = array();
                foreach ($currentSelectedGifts as $key => $selectGiftId) {
                    $parts = explode('_', $selectGiftId);
                    array_pop($parts);
                    $slimIds[] = implode('_', $parts);
                }
                $occurrences = array_count_values($slimIds);
                if (array_key_exists($giftItemKey, $occurrences)) {
                    $_selectedCount = $occurrences[$giftItemKey];
                }

                if ($_object->getAllowGiftSelectionCount() != 0
                    && $_selectedCount >= $_object->getAllowGiftSelectionCount()
                ) {
                    return false;
                }
            }

            //$this->cartItemToGifting($_object, $giftItemKey);
            return true;
        }

        return false;
    }


    /**
     * No idea why this exists.
     * Stripping it out solves quite a few strange behaviours
     *
     * @param $_object
     * @param $giftItemKey
     * @throws Mage_Core_Exception
     *
     * @depricated Gave more bugs to solve with cart items changing to gift items
     *
     */
    public function cartItemToGifting($_object, $giftItemKey)
    {
        $helper = mage::helper('giftpromo');
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quoteItems = $quote->setData('trigger_recollect', 0)->getAllVisibleItems();
        if (count($quoteItems) > 1) {
            $items = $_object->getItemsArray();
            foreach ($items as $_item) {
                if($_item->getTypeId() == 'gift-simple') {
                    // is any of the items in the cart?
                    // cannot use core getItemByProduct as it uses 'represent product' which may not
                    // match a real product to a gift product
                    foreach ($quoteItems as $quoteItem) {
                        if (!$helper->testGiftTypeCode($quoteItem->getProductType())) {
                            if ($quoteItem->getData('product_id') == $_item->getId()
                            ) {
                                // lets remove it, insert the gift, and set selected
                                // not sure why this is here, but it is intefering with BY x get X free.
                                if (!Mage::registry('is_injecting_x_x')) {
                                    $quote->removeItem($quoteItem->getId());
                                }
                                Mage::getSingleton('core/session')->getMessages(true);
                                Mage::getSingleton('checkout/session')->getMessages(true);
                                try {
                                    $_object->setAddToCartMessage('{NONE}');
                                    $helper->addGiftItems(
                                        array(
                                            $_item),
                                        $_object,
                                        array('qty' => 1,
                                              'populate_selected_gift_item_key' => true
                                        )
                                    );
                                    Mage::getSingleton('core/session')->getMessages(true);
                                    Mage::getSingleton('checkout/session')->getMessages(true);
                                    // flag as current selected gift
                                    $currentSelectedGifts = $helper->getCurrentSelectedGifts();
                                    $currentSelectedGifts[] = $giftItemKey . '_' . $_item->getId();
                                    $helper->setCurrentSelectedGifts(
                                        $currentSelectedGifts
                                    );

                                } catch (Exception $e) {
                                    //something wrong, clear selected data
                                    $helper->resetCurrentSelectedGift();
                                    //throw exception forward
                                    Mage::throwException($e->getMessage());
                                }
                            }
                        }
                    }
                }

            }
        }
    }

    /**
     * Check for Out Of Stock items and filter accordingly
     * //also filterout any products that are already in the cart
     *
     * @param object $object the product object
     *
     * @return array
     */
    public function filterItems($object)
    {
        if (Mage::getStoreConfig('giftpromo/cart/oos_enabled')) {
            return $object->getItems();
        } else {
            $_giftItems = array();
            $items = $object->getItemsArray();
            foreach ($items as $_item) {
                // only display products which are in stock
                if ($_item->isSaleable()) {
                    $_giftItems[] = $_item;
                }
            }
        }

        return $_giftItems;
    }

    /**
     * Is the product selected ?
     *
     * @param string $giftItemKey
     *
     * @return mixed boolean | integer
     */
    public function isCurrentSelected($giftItemKey)
    {
        $currentSelectedGifts = Mage::helper('giftpromo')->getCurrentSelectedGifts();
        if (in_array($giftItemKey, $currentSelectedGifts)) {
            $occurrences = array_count_values($currentSelectedGifts);
            if (array_key_exists($giftItemKey, $occurrences)) {
                return $occurrences[$giftItemKey];
            }
        }

        return false;
    }

    /**
     * Are there products selected.
     *
     * @param      $product
     * @param bool $parentItem
     * @param      $giftItemKey
     *
     * @return bool
     */
    public function hasCurrentSelected($product, $parentItem = false, $giftItemKey)
    {
        if ($product->getGiftRuleId()) {
            $currentSelectedGifts = Mage::helper('giftpromo')->getCurrentSelectedGifts();
            if (in_array($giftItemKey, $currentSelectedGifts)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get product thumbnail image
     *
     * @return Mage_Catalog_Model_Product_Image
     */
    public function getProductThumbnail($item)
    {
        $product = false;
        if ($option = $item->getOptionByCode('simple_product')) {
            $product = $option->getProduct();
        }

        if (!$product || !$product->getData('thumbnail')
            || ($product->getData('thumbnail') == 'no_selection')
            || (Mage::getStoreConfig(self::CONFIGURABLE_PRODUCT_IMAGE) == self::USE_PARENT_IMAGE)
        ) {
            $product = $item;
        }

        return $this->helper('catalog/image')->init($product, 'thumbnail');
    }

    public function getSelectGiftMessage($_object)
    {
        $parentName = $_object->getParentName();
        $messageTemplate = mage::getStoreConfig('giftpromo/cart/selectgifts_message');
        if (empty($messageTemplate)) {
            return $this->__(
                "<h2>You qualify for a promotion '%s', Please select %s product(s):</h2>",
                $this->getRuleNameParsed($_object->getRuleName()),
                $this->getSelectModeText($_object)
            );
        } else {
            $messageTemplate = str_replace('{PRODUCT_NAME}', $parentName, $messageTemplate);
            $messageTemplate = str_replace('{RULE_NAME}', $_object->getRuleName(), $messageTemplate);
            $messageTemplate = str_replace('{RULE_DESCRIPTION}', $_object->getDescription(), $messageTemplate);
            $messageTemplate = str_replace('{SELECT_COUNT}', $this->getSelectModeText($_object), $messageTemplate);
            $messageTemplate = $this->getRuleNameParsed($messageTemplate);

            return $messageTemplate;
        }
    }

    public function getRuleNameParsed($value)
    {
        if (preg_match("/{{PRICE:(.*)}}/s", $value, $result)) {
            if (count($result) == 2) {
                $convertedPriceValue = Mage::helper('core')->currencyByStore($result[1], null, true, false);
                $value = str_replace($result[0], $convertedPriceValue, $value);
            }
        }
        return $value;

    }

    public function getSelectModeText($object)
    {
        return $object->getAllowGiftSelectionCount();
    }

    /**
     * Prevents cart from displaying 'gift added' if youbrowse back to cart after adding via checkout
     */
    public function disableCartMessage()
    {
        $cart = Mage::helper('giftpromo')->getCartSession();
        $cart->getCheckoutSession()->setSkipGiftNotice(true);
    }

    public function canLinkItem($item)
    {
        return Mage::helper('giftpromo')->canLinkItem($item);
    }

    public function disableInCheckout()
    {
        return Mage::getStoreConfig('giftpromo/checkout/review_select_disabled');
    }

    protected function _construct()
    {
        parent::_construct();
        $this->addData(
            array(
                'cache_lifetime' => null,
                'cache_tags' => array(
                    Mage_Cms_Model_Block::CACHE_TAG)
            )
        );
    }

    /**
     * Before html call
     *
     * @return object
     */
    protected function _beforeToHtml()
    {
        $this->_prepareData();

        return parent::_beforeToHtml();
    }

    /**
     * Prepare the data for display
     */
    protected function _prepareData()
    {
        try {
            $timesRuleUsed = array();
            $this->_rules = array();
            $quote = $this->getQuote();
            $address = $quote->getShippingAddress();
            // check rule based gifts
            $store = Mage::app()->getStore($quote->getStoreId());
            $validator = Mage::getSingleton('giftpromo/promo_validator');
            $validator->init(
                $store->getWebsiteId(),
                $quote->getCustomerGroupId(),
                $quote->getCouponCode()
            );
            $rules = Mage::getModel('giftpromo/promo_rule')->getCollection()->load();
            $this->_itemCollection = new Varien_Data_Collection;
            $rulesArray = $rules->asArray();
            $skipOtherRules = false;
            foreach ($rulesArray as $rulesByOrder) {
                if ($skipOtherRules) {
                    break;
                }
                foreach ($rulesByOrder as $ruleObject) {
                    if ($validator->canProcessRule(
                        $ruleObject,
                        $address
                    )
                    ) {
                        if ($ruleObject->getAllowGiftSelection() && $ruleObject->validate($quote)) {

                            $ruleObject->setForName($ruleObject->getRuleName());
                            try {
                                //how many gifts can be added on this rule....
                                $validItems = $ruleObject->validateCount($quote);
                                $originalId = $ruleObject->getId();
                                foreach ($validItems as $itemCounted) {
                                    // check if rule has usage count
                                    if ($ruleObject->getUsesPerRule() > 0) {
                                        if (!array_key_exists($ruleObject->getId(), $timesRuleUsed)) {
                                            $timesRuleUsed[$ruleObject->getId()] = 0;
                                        }
                                        $timesUsed = $timesRuleUsed[$ruleObject->getId()];
                                        $usesPerRule = $ruleObject->getUsesPerRule();
                                        if ($timesUsed >= $usesPerRule) {
                                            continue;
                                        }
                                        $timesUsed++;
                                        $timesRuleUsed[$ruleObject->getId()] = $timesUsed;
                                    }
                                    $itemName = $itemCounted->getName();
                                    $copiedRuleObject = clone $ruleObject;
                                    $copiedRuleObject->setParentName($itemName);
                                    $copiedRuleObject->setId($originalId . '_' . $itemCounted->getId());
                                    $this->_itemCollection->addItem($copiedRuleObject);
                                }
                            } catch (Exception $e) {
                                // fail silently, as the item already exists as a gift, thus don't add it again
                            }
                        }
                        if ($ruleObject->validate($quote)
                            && $ruleObject->getStopRulesProcessing($rules, $quote)
                        ) {
                            $skipOtherRules = true;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            if (Mage::getIsDeveloperMode()) {
                die($e->getMessage());
            }
        }
    }

    /**
     * Load block html from cache storage
     *
     * @return string | false
     */
    protected function _loadCache()
    {
        return false;
    }

    /**
     * Save block content to cache storage
     *
     * @param string $data
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _saveCache($data)
    {
        return false;
    }

}
