<?php

/**
 * Class ProxiBlue_GiftPromo_Model_Promo_Api_V2
 */
class ProxiBlue_GiftPromo_Model_Promo_Api_V2 extends Mage_Api_Model_Resource_Abstract
{
    private function __($str = '')
    {
        return Mage::helper('giftpromo')->__($str);
    }

    /**
     * List all or selected promotional rules.
     *
     * @param $ruleId
     * @param $websiteId
     *
     * @throws Exception
     * @return array
     */
    public function listPromotionRules($ruleId = 0, $websiteId = 0)
    {
        /* @var ProxiBlue_GiftPromo_Model_Resource_Promo_Rule_Collection $collection */
        $collection = Mage::getModel('giftpromo/promo_rule')->getResourceCollection();

        /* add rule_id filter */
        if ($ruleId) {
            $collection->addFieldToFilter('rule_id', $ruleId);
        }

        /* add website_ids filter if passed */
        if ($websiteId) {
            $collection->addWebsiteFilter($websiteId);
        }

        $rules = array();
        try {
            /* @var ProxiBlue_GiftPromo_Model_Promo_Rule $rule */
            foreach ($collection as $rule) {
                $rule = $rule->getData();
                if (!empty($rule['conditions_serialized'])) {
                    $conditions = unserialize($rule['conditions_serialized']);
                    foreach ($conditions['conditions'] as $condition) {
                        foreach ($condition['conditions'] as $_condition) {
                            $_condition['sku'] = $_condition['value'];
                            $rule['conditions'][] = $_condition;
                        }
                    }
                }
                if (!empty($rule['giftpromo'])) {
                    list($idSku, $giftedMsg, $giftedLbl, $giftedPos, $giftedQtyMax, $rate) = explode(
                        '|', urldecode($rule['giftpromo'])
                    );

                    list($productId, $productPrice) = explode('=', $idSku);
                    $rate = explode(':', trim($rate, ':'));
                    $rateProductQty = $rateGiftRate = 1;
                    if (count($rate) == 2) {
                        $rateProductQty = $rate[0];
                        $rateGiftRate = $rate[1];
                    }

                    $rule['promotion'] = array(
                        array(
                            'sku'              => Mage::getModel('catalog/product')->load($productId)->getSku(),
                            'price'            => $productPrice,
                            'gifted_message'   => $giftedMsg,
                            'gifted_label'     => $giftedLbl,
                            'gifted_position'  => $giftedPos,
                            'gifted_qty_max'   => $giftedQtyMax,
                            'rate_product_qty' => $rateProductQty,
                            'rate_gift_rate'   => $rateGiftRate,
                        )
                    );
                }

                $rule['website_ids'] = explode(',', $rule['website_ids']);
                $rule['customer_ids'] = explode(',', $rule['customer_ids']);
                $rules[] = $rule;
            }
        } catch (Exception $e) {
            $this->_apiFault($e->getMessage());
        }

        return $rules;
    }

    /**
     * remove promotional rule
     *
     * @param $ruleId int
     *
     * @return boolean
     */
    public function removePromotionRule($ruleId = 0)
    {
        if (!$ruleId) {
            $this->_apiFault($this->__('Rule Id is required!'));
        }
        /* @var ProxiBlue_GiftPromo_Model_Resource_Promo_Rule_Collection $collection */
        $collection = Mage::getModel('giftpromo/promo_rule')->getResourceCollection()->addFieldToFilter(
            'rule_id', $ruleId
        );

        try {
            /* @var ProxiBlue_GiftPromo_Model_Promo_Rule $rule */
            foreach ($collection as $rule) {
                $rule->delete();
            }
        } catch (Exception $e) {
            $this->_apiFault($e->getMessage());
        }

        return true;
    }

    /**
     * Update existing promotional rule
     *
     * @param $filter
     * @param $data
     *
     * @throws Exception
     */
    public function updatePromotionRule($filter, $data)
    {
        try {

        } catch (Exception $e) {
            $this->_apiFault($e->getMessage());
        }
    }

    /**
     * Create a simple BOGOF promotional rule
     *
     * @param array $data
     *
     * @return int
     */
    public function addPromotionRule($data)
    {
        /** @var ProxiBlue_GiftPromo_Model_Promo_Rule $model */
        $model = Mage::getModel('giftpromo/promo_rule');
        try {
            $data = Mage::helper('dsecore')->objectToArray($data);
            if (empty($data['website_ids']) && !empty($data['websites'])) {
                $data['website_ids'] = Mage::helper('dsecore/store')->getWebsiteIdsByCode($data['websites']);
            }

            //format data for rule creation
            $rule = [
                'is_active'                  => $data['is_active'],
                'rule_name'                  => $data['rule_name'],
                'description'                => $data['description'],
                'from_date'                  => $data['from_date'],
                'to_date'                    => $data['to_date'],
                'customer_ids'               => implode(',', $data['customer_ids']),
                'website_ids'                => implode(',', $data['website_ids']),
                'uses_per_customer'          => 1,
                'usage_limit'                => $data['usage_limit'],
                'stop_rules_processing'      => 0,
                'allow_gift_selection_count' => $data['allow_gift_selection_count'],
                'gift_added_product'         => 0,
                'conditions'                 => $this->prepareConditions($data['conditions']),
                'giftpromo'                  => $this->preparePromotionalItems($data['promotion'])
            ];
            $model->loadPost($rule);
            $model->save();
        } catch (Exception $e) {
            $this->_apiFault($e->getMessage());
        }

        return $model->getId();
    }

    /**
     * Prepares cart conditions
     *
     * @param $conditions
     *
     * @return array
     * @throws Mage_SalesRule_Exception
     */
    protected function prepareConditions($conditions)
    {
        $formatted = [
            '1'    => ['type' => 'giftpromo/promo_rule_condition_combine', 'new_child' => ''],
            '1--1' => ['type'      => 'giftpromo/promo_rule_condition_product_found', 'value' => 1,
                       'agregator' => 'all', 'new_child' => '']
        ];

        if (empty($conditions)) {
            $this->_apiFault('Required cart rule SKU is empty.');
        }

        foreach ($conditions as $k => $v) {
            $value = array_map('trim', explode(",", $v['value']));

            if ($v['attribute'] == 'sku') {
                foreach ($value as $sku) {
                    $id = Mage::getModel("catalog/product")->getIdBySku($sku);
                    if (!$id) {
                        $this->_apiFault('Required cart rule SKU ' . $sku . ' does not exist');
                    }
                }
            }
            $values = implode(',', $value);
            $formatted['1--1--' . ($k)] = [
                'type'      => 'salesrule/rule_condition_product',
                'attribute' => $v['attribute'],
                'operator'  => $v['operator'],
                'value'     => $values
            ];
        }

        return $formatted;
    }

    /**
     * Prepares promotional items
     *
     * @param $promotionItems
     *
     * @return string
     * @throws Mage_SalesRule_Exception
     */
    protected function preparePromotionalItems($promotionItems)
    {
        $giftPromo = array();
        foreach ($promotionItems as $v) {
            $id = Mage::getModel("catalog/product")->getIdBySku($v['sku']);
            if (!$id) {
                $this->_apiFault('Promotional SKU ' . $v['key'] . ' does not exist');
            }
            //@see giftpromo_promo_rule table
            $promoRule = $id . '=' . ((float)$v['price']) .
                urlencode(
                    '|' . $this->_getValue($v, 'gifted_message') .
                    '|' . $this->_getValue($v, 'gifted_label') .
                    '|' . $this->_getValue($v, 'gifted_position') .
                    '|' . $this->_getValue($v, 'gifted_qty_max') .
                    '|:' . $this->_getValue($v, 'rate_product_qty') .
                    ':' . $this->_getValue($v, 'rate_gift_rate')
                );
            $giftPromo[] = $promoRule; //$id . '=' . ((float)$v['price']) . '%7C%7C%7C%7C0%7C%3A%3A';
        }

        return implode('&', $giftPromo);
    }

    private function _getValue($array, $key)
    {
        if (!is_array($array)) {
            return $array;
        }

        if (!isset($array[$key])) {
            return '';
        }

        return $array[$key];
    }

    protected function _apiFault($msg, $code = 'data_invalid')
    {
        Mage::log($msg, null, 'giftpromo_api_faults.log');
        throw new Mage_Api_Exception($code, $msg);
    }
}