<?php

/**
 * Base observer class extended by module observers
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
class ProxiBlue_GiftPromo_Model_Coupons extends ProxiBlue_GiftPromo_Model_Observer
{
    const COUPON_ON_ACCOUNT_CREATE = 1;
    const COUPON_ON_NEWSLETTER = 2;
    const COUPON_ON_BDAY = 3;

    /**
     * Generate a coupon if allowed
     *
     * @param type $observer
     */
    public function newsletter_subscriber_save_before($observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        if ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            // first get all/any rules that allow for newsletter coupon generation
            $todayDate = Mage::app()->getLocale()->date()->toString(
                Varien_Date::DATE_INTERNAL_FORMAT
            );
            $rules = Mage::getResourceModel('giftpromo/promo_rule_collection')
                ->addFieldToFilter(
                    'coupon_type', array('eq' => ProxiBlue_GiftPromo_Model_Promo_Rule::COUPON_TYPE_SPECIFIC)
                )
                ->addFieldToFilter('use_auto_generation', array('eq' => 1))
                ->addFieldToFilter('generate_coupon', array('eq' => self::COUPON_ON_NEWSLETTER))
                ->addfieldtofilter(
                    'coupon_gen_to',
                    array(
                        array('gteq' => $todayDate),
                        array('coupon_gen_to', 'null' => ''))
                )
                ->addFieldToFilter('is_active', array('eq' => 1));
            $this->_getHelper()->debug('NEWSLETTER SQL: ' . $rules->getSelect(), 50);
            foreach ($rules as $rule) {
                $couponCode = mage::helper('giftpromo/coupon')->generateCouponCode($rule);
                if ($couponCode) {
                    // email it
                    mage::helper('giftpromo/coupon')->emailCouponCode(
                        $subscriber->getSubscriberEmail(), $couponCode, $subscriber, $rule
                    );
                } else {
                    $this->_getHelper()->debug(
                        'NEWSLETTER SUBSCRIBE: not a avlid generated coupon. Check exception logs. '
                        . $subscriber->getSubscriberEmail(), 50
                    );
                }
            }
        } else {
            $this->_getHelper()->debug(
                'NEWSLETTER SUBSCRIBE: cannot action as subscriber status is not subscribed. '
                . $subscriber->getSubscriberStatus(), 50
            );
        }

    }

    /**
     * Generate a coupn for birthday cron
     *
     * @param $schedule
     */
    public static function birthday_cron($schedule)
    {
        // first get all/any rules that allow for newsletter coupon generation
        $todayDate = Mage::app()->getLocale()->date()->toString(
            Varien_Date::DATE_INTERNAL_FORMAT
        );
        $rules = Mage::getResourceModel('giftpromo/promo_rule_collection')
            ->addFieldToFilter(
                'coupon_type', array('eq' => ProxiBlue_GiftPromo_Model_Promo_Rule::COUPON_TYPE_SPECIFIC)
            )
            ->addFieldToFilter('use_auto_generation', array('eq' => 1))
            ->addFieldToFilter('generate_coupon', array('eq' => self::COUPON_ON_BDAY))
            ->addfieldtofilter(
                'coupon_gen_to',
                array(
                    array('gteq' => $todayDate),
                    array('coupon_gen_to', 'null' => ''))
            )
            ->addFieldToFilter('is_active', array('eq' => 1));
        if ($rules->count() > 0) {
            $customerCollection = mage::getModel('customer/customer')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter(
                    'dob',
                    array(
                        array('eq' => $todayDate),
                    )
                );
            mage::log(
                'BDAY CUSTOMER SQL: '
                . $customerCollection->getSelect()
            );

            foreach ($customerCollection as $customer) {
                if ($customer->getIsActive() == 0) {
                    continue;
                }
                foreach ($rules as $rule) {
                    // customer must exists in the store defined in the rule
                    if (!in_array($customer->getWebsiteId(), $rule->getWebsiteIds())) {
                        mage::log(
                            'BDAY COUPON: Customer does not exist in configured website for rule. '
                            . $customer->getEmail()
                        );
                        continue;
                    }
                    if (!in_array($customer->getGroupId(), $rule->getCustomerIds())) {
                        mage::log(
                            'BDAY COUPON: Customer does not exist in configured group for rule. '
                            . $customer->getEmail()
                        );
                        continue;
                    }
                    $couponCode = mage::helper('giftpromo/coupon')->generateCouponCode($rule);
                    if ($couponCode) {
                        // email it
                        mage::helper('giftpromo/coupon')->emailCouponCode(
                            $customer->getEmail(), $couponCode, $customer, $rule
                        );
                    } else {
                        mage::log(
                            'BDAY COUPON: not a valid generated coupon. Check exception logs. '
                            . $customer->getEmail()
                        );
                    }
                }
            }
        }

    }

    /**
     * Generate coupon for customer
     *
     * @param $observer
     */
    public function customer_save_before($observer)
    {
        $_customer = $observer->getCustomer();
        if (!$_customer->getId()) {
            $todayDate = Mage::app()->getLocale()->date()->toString(
                Varien_Date::DATE_INTERNAL_FORMAT
            );
            $rules = Mage::getResourceModel('giftpromo/promo_rule_collection')
                ->addFieldToFilter(
                    'coupon_type', array('eq' => ProxiBlue_GiftPromo_Model_Promo_Rule::COUPON_TYPE_SPECIFIC)
                )
                ->addFieldToFilter('use_auto_generation', array('eq' => 1))
                ->addFieldToFilter('generate_coupon', array('eq' => self::COUPON_ON_ACCOUNT_CREATE))
                ->addfieldtofilter(
                    'coupon_gen_to',
                    array(
                        array('gteq' => $todayDate),
                        array('coupon_gen_to', 'null' => ''))
                )
                ->addFieldToFilter('is_active', array('eq' => 1));
            $this->_getHelper()->debug('CREATE ACCOUNT SQL: ' . $rules->getSelect(), 50);
            foreach ($rules as $rule) {
                $couponCode = mage::helper('giftpromo/coupon')->generateCouponCode($rule);
                if ($couponCode) {
                    // email it
                    mage::helper('giftpromo/coupon')->emailCouponCode(
                        $_customer->getEmail(), $couponCode, $_customer, $rule
                    );
                } else {
                    $this->_getHelper()->debug(
                        'CUSTOMER CREATE: not a valid generated coupon. Check exception logs. '
                        . $_customer->getSubscriberEmail(), 50
                    );
                }
            }
        }
    }

}

?>
