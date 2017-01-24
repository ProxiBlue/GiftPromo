<?php

/**
 * sales admin controller
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_GiftPromo
 * @author     Lucas van Staden (support@proxiblue.com.au)
 */
require_once(Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Promo' . DS . 'QuoteController.php');

class ProxiBlue_GiftPromo_Adminhtml_Promo_RuleController extends Mage_Adminhtml_Promo_QuoteController
{

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('promo');
        $this->_addBreadcrumb(
            Mage::helper('adminhtml')->__('Promotions'), Mage::helper('adminhtml')->__('GiftPromo Rules')
        );
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->loadLayout();

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('giftpromo/promo_rule');

        if ($id) {
            $model->load($id);
            if (!$model->getRuleId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('giftpromo')->__('The rule no longer exists to edit.')
                );
                $this->_redirect('*/*');

                return;
            }
        }

        $data = Mage::getSingleton('adminhtml/session')->getPageData(true);
        if (isset($data['icon_file'])) {
            unset($data['icon_file']);
        }

        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        //$model->getActions()->setJsFormObject('rule_actions_fieldset');

        Mage::register('current_giftpromo_promo_rule', $model, true);


        $this->renderLayout();
    }

    /**
     * Promo quote save action
     *
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            try {
                /** @var $model Mage_SalesRule_Model_Rule */
                $model = Mage::getModel('giftpromo/promo_rule');

                unset($data['form_key']);
                unset($data['rule_id']);

                if (!empty($data['from_date'])) {
                    $data = $this->_filterDates($data, array('from_date', 'to_date', 'coupon_gen_to'));
                }

                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        Mage::throwException(Mage::helper('giftpromo')->__('Wrong rule specified.'));
                    }
                }

                $session = Mage::getSingleton('adminhtml/session');

                $validateResult = $model->validateData(new Varien_Object($data));

                if ($validateResult !== true) {
                    foreach ($validateResult as $errorMessage) {
                        $session->addError($errorMessage);
                    }
                    $session->setPageData($data);
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));

                    return;
                }


                if (isset($data['website_ids']) && is_array($data['website_ids'])) {
                    $data['website_ids'] = implode(',', $data['website_ids']);
                }

                if (isset($data['customer_ids']) && is_array($data['customer_ids'])) {
                    $data['customer_ids'] = implode(',', $data['customer_ids']);
                }

                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }

                if (isset($data['allow_gift_selection_count'])
                    && empty($data['allow_gift_selection_count'])
                    || $data['allow_gift_selection_count'] == 0
                ) {
                    $data['allow_gift_selection_count'] = 1;
                }

                if (!isset($data['gift_added_product'])) {
                    $data['gift_added_product'] = 0;
                }

                if (!isset($data['gift_add_product_multi'])) {
                    $data['gift_add_product_multi'] = 0;
                }

                if (!isset($data['gift_add_normal_product'])) {
                    $data['gift_add_normal_product'] = 0;
                }

                if (isset($data['icon_file']) && is_array($data['icon_file'])
                    && isset($data['icon_file']['delete'])
                    && $data['icon_file']['delete'] == 1
                ) {
                    try {
                        unlink(Mage::getBaseDir('media') . DS . $data['icon_file']['value']);
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                    $data['icon_file'] = '';
                }

                if (isset($_FILES['icon_file']['name']) and (file_exists($_FILES['icon_file']['tmp_name']))) {
                    try {
                        $uploader = new Varien_File_Uploader('icon_file');
                        $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png')); // or pdf or anything
                        $uploader->setAllowRenameFiles(false);
                        // setAllowRenameFiles(true) -> move your file in a folder the magento way
                        $uploader->setFilesDispersion(false);
                        $path = Mage::getBaseDir('media') . DS . 'giftpromo' . DS;
                        $uploader->save($path, $model->getRuleId() . "_" . $_FILES['icon_file']['name']);
                        $data['icon_file'] = 'giftpromo' . DS . $uploader->getUploadedFileName();
                        try {
                            unlink(Mage::getBaseDir('media') . DS . $model->getIconFile());
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                    } catch (Exception $e) {

                    }
                }

                if (isset($data['icon_file']) && is_array($data['icon_file'])) {
                    Mage::log('gift promo icon file hard reset occured');
                    unset($data['icon_file']);
                }
                unset($data['rule']);
                $model->loadPost($data);

                $session->setPageData($model->getData());

                //unset use_auto_generation if not in data
                if (!array_key_exists('use_auto_generation', $data)) {
                    $model->setUseAutoGeneration(0);
                } else {
                    $model->setUseAutoGeneration(1);
                }
                $model->save();

                $session->addSuccess(Mage::helper('giftpromo')->__('The rule has been saved.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));

                    return;
                }
                $this->_redirect('*/*/');

                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('rule_id');
                if (!empty($id)) {
                    $this->_redirect('*/*/edit', array('id' => $id));
                } else {
                    $this->_redirect('*/*/new');
                }

                return;
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('catalogrule')->__(
                        'An error occurred while saving the rule data. Please review the log and try again.'
                    )
                );
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('rule_id')));

                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Promo quote save action
     *
     */
    public function deleteAction()
    {

        try {
            $model = Mage::getModel('giftpromo/promo_rule');
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
                if ($id != $model->getId()) {
                    Mage::throwException(Mage::helper('giftpromo')->__('Wrong rule specified.'));
                }
                $model->delete();
                $session = Mage::getSingleton('adminhtml/session');
                $session->addSuccess(Mage::helper('giftpromo')->__('The rule has been removed.'));
                $session->setPageData(false);
                $this->_redirect('*/*/');

                return;
            }
        } catch (Exception $e) {
            $this->_getSession()->addError(
                Mage::helper('catalogrule')->__(
                    'An error occurred while removing the rule data. Please review the log and try again.'
                )
            );
            Mage::logException($e);
            $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));

            return;
        }
        $this->_redirect('*/*/');

        return;
    }

    /**
     * Rebuild actions grid
     */
    public function giftPromoGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('giftpromo/promo_rule')->load($id);
        Mage::register('current_giftpromo_promo_rule', $model);
        $block = $this->getLayout()->createBlock(
            'giftpromo/adminhtml_promo_rule_edit_tab_actions_giftpromo_grid',
            'giftpromo_promo_rule_edit_tab_actions_giftpromo_grid'
        );
        $this->getResponse()->setBody($block->toHtml());
        $this->renderLayout();
    }

    /**
     * Generate Coupons action
     */
    public function generateAction()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noRoute');

            return;
        }
        $result = array();
        $this->_initRule();

        /** @var $rule Mage_SalesRule_Model_Rule */
        $rule = Mage::registry('current_giftpromo_promo_rule');

        if (!$rule->getId()) {
            $result['error'] = Mage::helper('salesrule')->__('Rule is not defined');
        } else {
            try {
                $data = $this->getRequest()->getParams();
                if (!empty($data['to_date'])) {
                    $data = array_merge($data, $this->_filterDates($data, array('to_date')));
                }

                /** @var $generator Mage_SalesRule_Model_Coupon_Massgenerator */
                $generator = $rule->getCouponMassGenerator();
                if (!$generator->validateData($data)) {
                    $result['error'] = Mage::helper('salesrule')->__('Not valid data provided');
                } else {
                    $generator->setData($data);
                    $generator->generatePool();
                    $generated = $generator->getGeneratedCount();
                    $this->_getSession()->addSuccess(
                        Mage::helper('salesrule')->__('%s Coupon(s) have been generated', $generated)
                    );
                    $this->_initLayoutMessages('adminhtml/session');
                    $result['messages'] = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
                }
            } catch (Mage_Core_Exception $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                $result['error'] = Mage::helper('salesrule')->__(
                    'An error occurred while generating coupons. Please review the log and try again.'
                );
                Mage::logException($e);
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _initRule()
    {
        $this->_title($this->__('Promotions'))->_title($this->__('Gift Promotions'));

        Mage::register('current_giftpromo_promo_rule', Mage::getModel('giftpromo/promo_rule'));
        $id = (int)$this->getRequest()->getParam('id');

        if (!$id && $this->getRequest()->getParam('rule_id')) {
            $id = (int)$this->getRequest()->getParam('rule_id');
        }

        if ($id) {
            Mage::registry('current_giftpromo_promo_rule')->load($id);
        }
    }

    /**
     * Coupon codes grid
     */
    public function couponsGridAction()
    {
        $this->_initRule();
        $this->loadLayout()->renderLayout();
        //$this->_forward('edit');
    }

    /**
     * Export coupon codes as excel xml file
     *
     * @return void
     */
    public function exportCouponsXmlAction()
    {
        $this->_initRule();
        $rule = Mage::registry('current_giftpromo_promo_rule');
        if ($rule->getId()) {
            $fileName = 'gift_coupon_codes.xml';
            $content = $this->getLayout()
                ->createBlock('giftpromo/adminhtml_promo_rule_edit_tab_coupons_grid')
                ->getExcelFile($fileName);
            $this->_prepareDownloadResponse($fileName, $content);
        } else {
            $this->_redirect('*/*/detail', array('_current' => true));

            return;
        }
    }

    /**
     * Export coupon codes as CSV file
     *
     * @return void
     */
    public function exportCouponsCsvAction()
    {
        $this->_initRule();
        $rule = Mage::registry('current_giftpromo_promo_rule');
        if ($rule->getId()) {
            $fileName = 'gift_coupon_codes.csv';
            $content = $this->getLayout()
                ->createBlock('giftpromo/adminhtml_promo_rule_edit_tab_coupons_grid')
                ->getCsvFile();
            $this->_prepareDownloadResponse($fileName, $content);
        } else {
            $this->_redirect('*/*/detail', array('_current' => true));

            return;
        }
    }

    /**
     * Coupons mass delete action
     */
    public function couponsMassDeleteAction()
    {
        $this->_initRule();
        $rule = Mage::registry('current_giftpromo_promo_rule');

        if (!$rule->getId()) {
            $this->_forward('noRoute');
        }

        $codesIds = $this->getRequest()->getParam('ids');

        if (is_array($codesIds)) {

            $couponsCollection = Mage::getResourceModel('giftpromo/promo_coupon_collection')
                ->addFieldToFilter('coupon_id', array('in' => $codesIds));

            foreach ($couponsCollection as $coupon) {
                $coupon->delete();
            }
        }
    }

    public function massDeleteAction()
    {
        $ruleIds = $this->getRequest()->getParam('rule_id');
        if (!is_array($ruleIds)) {
            $this->_getSession()->addError($this->__('Please select rule(s).'));
        } else {
            if (!empty($ruleIds)) {
                try {
                    foreach ($ruleIds as $ruleId) {
                        $ruleModel = Mage::getModel('giftpromo/promo_rule')->load($ruleId);
                        Mage::dispatchEvent('giftpromo_controller_rule_delete', array('product' => $ruleId));
                        $ruleModel->delete();
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) have been deleted.', count($ruleIds))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Update rule status action
     *
     */
    public function massStatusAction()
    {
        $ruleIds = $this->getRequest()->getParam('rule_id');
        $status = (int)$this->getRequest()->getParam('status');
        if (!is_array($ruleIds)) {
            $this->_getSession()->addError($this->__('Please select rule(s).'));
        } else {
            if (!empty($ruleIds)) {
                try {
                    foreach ($ruleIds as $ruleId) {
                        $ruleModel = Mage::getModel('giftpromo/promo_rule')->load($ruleId);
                        $ruleModel->setIsActive($status);
                        $ruleModel->save();
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) status has been changed.', count($ruleIds))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Update rule status action
     *
     */
    public function massDuplicateAction()
    {
        $ruleIds = $this->getRequest()->getParam('rule_id');
        if (!is_array($ruleIds)) {
            $this->_getSession()->addError($this->__('Please select rule(s).'));
        } else {
            if (!empty($ruleIds)) {
                try {
                    foreach ($ruleIds as $ruleId) {
                        $ruleModel = Mage::getModel('giftpromo/promo_rule')->load($ruleId);
                        $ruleModel->setIsActive(0);
                        $ruleModel->unsRuleId();
                        $ruleModel->setRuleName('Duplicate of ' . $ruleModel->getRuleName());
                        $ruleModel->save();

                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) status has been changed.', count($ruleIds))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('promo');
    }

}
