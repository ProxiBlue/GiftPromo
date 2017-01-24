<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Twitter_Conditions extends Mage_Rule_Model_Condition_Combine
{

    public function __construct()
    {
        parent::__construct();
        $this->setType('giftpromo/promo_rule_condition_Twitter_conditions')
            ->setValue(null);
    }

    public function asHtmlRecursive()
    {
        $html = $this->asHtml() . '<ul id="' . $this->getPrefix() . '__' . $this->getId()
            . '__children" class="rule-param-children">';
        if (self::getTwitterHandle()) {
            foreach ($this->getConditions() as $cond) {
                $html .= '<li>' . $cond->asHtmlRecursive() . '</li>';
            }
            $html .= '<li>' . $this->getNewChildElement()->getHtml() . '</li></ul>';
        }

        return $html;
    }

    public function asHtml()
    {
        if (self::getTwitterHandle()) {
            $html = $this->getTypeElement()->getHtml() .
                Mage::helper('rule')->__(
                    'If %s these conditions are %s:', $this->getAggregatorElement()->getHtml(),
                    $this->getValueElement()->getHtml()
                );
            if ($this->getId() != '1') {
                $html .= $this->getRemoveLinkHtml();
            }
        } else {
            $html = "You need to configure your Twitter details in the admin configuration.";
            $html .= $this->getRemoveLinkHtml();
        }

        return $html;
    }

    static function getTwitterHandle()
    {
        return mage::getStoreConfig('giftpromo/twitter/twitter_handle');
    }

    public function asString($format = '')
    {
        $str = Mage::helper('rule')->__("If these conditions are %s:", $this->getValueName());

        return $str;
    }

    /**
     * Conditions child rules
     * Current supported:
     * Cart Subtotal
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions, array(
                array('value' => 'giftpromo/promo_rule_condition_twitter_conditions_follow',
                      'label' => Mage::helper('giftpromo')->__('Follows')),
            )
        );

        return $conditions;
    }

    public function validate(Varien_Object $object)
    {
        $all = $this->getAggregator() === 'all';
        $true = (bool)$this->getValue();
        $found = false;
        $found = $all;
        foreach ($this->getConditions() as $cond) {
            $validated = $cond->validate($object);
            if (($all && !$validated) || (!$all && $validated)) {
                $found = $validated;
                break;
            }
        }
        if (($found && $true) || (!$true && $found)) {
            break;
        }
        // found an item and we're looking for existing one
        if ($found && $true) {
            return true;
        } // not found and we're making sure it doesn't exist
        elseif (!$found && !$true) {
            return true;
        }

        return false;
    }

}
