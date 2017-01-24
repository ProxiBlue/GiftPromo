<?php

/**
 * Gift product link model
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 *
 * */
class ProxiBlue_GiftPromo_Model_Product_Link extends Mage_Catalog_Model_Product_Link
{

    const RULE_TYPE = 1;

    public function __construct()
    {
        parent::__construct();
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_read');
        $table = $resource->getTableName('catalog_product_link_type');
        $sql = "SELECT * FROM {$table} WHERE `code` = 'gift_products'";
        $this->setLinkTypeId($connection->fetchOne($sql));
    }

    /**
     * Retrieve table name for attribute type
     *
     * @param   string $type
     *
     * @return  string
     */
    public function getAttributeTypeTable($type)
    {
        return $this->_getResource()->getAttributeTypeTable($type);
    }

    /**
     * Save data for product relations
     *
     * @param   Mage_Catalog_Model_Product $product
     *
     * @return  Mage_Catalog_Model_Product_Link
     */
    public function saveProductRelations($product)
    {
        $data = $product->getGiftPromoLinkData();
        if (!is_null($data) && is_array($data)) {
            foreach ($data as $key => $linkInfo) {
                // some defaults
                if (array_key_exists('gifted_message', $linkInfo) && strlen(trim($linkInfo['gifted_message'])) == 0) {
                    $data[$key]['gifted_message'] = Mage::helper('giftpromo')->__("Gift for %s", $product->getName());
                }
                if (array_key_exists('gifted_label', $linkInfo) && strlen(trim($linkInfo['gifted_label'])) == 0) {
                    $data[$key]['gifted_label'] = Mage::helper('giftpromo')->__("Gift Product");
                }
                if (array_key_exists('gifted_price', $linkInfo) && strlen(trim($linkInfo['gifted_price'])) == 0) {
                    $data[$key]['gifted_price'] = "0.00";
                }
                if (array_key_exists('gifted_qty_max', $linkInfo) && strlen(trim($linkInfo['gifted_qty_max'])) == 0) {
                    $data[$key]['gifted_qty_max'] = 0;
                }
                if (array_key_exists('position', $linkInfo) && strlen(trim($linkInfo['position'])) == 0) {
                    $data[$key]['position'] = 0;
                }
                $ruleModel = Mage::getModel('giftpromo/rule');
                if (array_key_exists('gifted_conditions', $linkInfo)) {
                    // save the rule data, and set the rule id that was used.
                    if (array_key_exists('rule_id', $linkInfo) && $linkInfo['rule_id'] != 0) {
                        $ruleModel->setId($linkInfo['rule_id']);
                    }
                    $ruleModel->setProductId($key);

                    $conditions = $linkInfo['gifted_conditions'];
                    if (is_array($conditions)) {
                        try {
                            $ruleModel->loadPost(array('conditions' => $conditions));
                            $ruleModel->setDiscountTypeId(self::RULE_TYPE);
                            $ruleModel->save();
                            $data[$key]['rule_id'] = $ruleModel->getId();
                            unset($data[$key]['gifted_conditions']);
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                    }
                } else {
                    if (array_key_exists('rule_id', $linkInfo) && $linkInfo['rule_id'] > 0) {
                        $ruleModel->load($linkInfo['rule_id']);
                        $ruleModel->delete();
                        $data[$key]['rule_id'] = 0;
                    }
                }
            }
            $this->_getResource()->saveProductLinks($product, $data, $this->getLinkTypeId());
        }

        return $this;
    }

    /**
     * Retrieve linked product collection
     */
    public function getProductCollection()
    {
        $collection = Mage::getResourceModel('giftpromo/product_link_product_collection')
            ->setLinkModel($this);

        return $collection;
    }

}
