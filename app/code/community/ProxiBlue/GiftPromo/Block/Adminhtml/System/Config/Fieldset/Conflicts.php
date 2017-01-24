<?php

/**
 * Render Module debug conflicts check
 *
 * @category  ProxiBlue
 * @package   GiftPromo
 * @author    Lucas van Staden <sales@proxiblue.com.au>
 * @copyright 2016 Lucas van Staden (ProxiBlue)
 * @license   http://www.proxiblue.com.au/eula EULA
 * @link      http://www.proxiblue.com.au
 */
class ProxiBlue_GiftPromo_Block_Adminhtml_System_Config_Fieldset_Conflicts extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{

    protected $_template = 'giftpromo/system/config/fieldset/conflicts.phtml';


    /**
     * Render fieldset html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }


    public function getCollection()
    {
        $fileName = 'config.xml';
        $modules = Mage::getConfig()->getNode('modules')->children();
        $return = array();
        foreach ($modules as $modName => $module) {
            if ($module->is('active')) {
                $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . $fileName;
                if (file_exists($configFile)) {
                    $xml = file_get_contents($configFile);
                    $xml = simplexml_load_string($xml);
                    if ($xml instanceof SimpleXMLElement) {
                        $return[$modName] = $xml->xpath('//rewrite');
                    }
                }
            }
        }
        $collection = new Varien_Data_Collection();
        foreach ($return as $rewriteNodes) {
            foreach ($rewriteNodes as $n) {
                $nParent = $n->xpath('..');
                $module = (string)$nParent[0]->getName();
                $nParent2 = $nParent[0]->xpath('..');
                $component = (string)$nParent2[0]->getName();
                if (!in_array($component, array('blocks', 'helpers', 'models'))) {
                    continue;
                }
                $pathNodes = $n->children();
                foreach ($pathNodes as $pathNode) {
                    $path = (string)$pathNode->getName();
                    $completePath = $module . '/' . $path;
                    $rewriteClassName = (string)$pathNode;
                    $instance = Mage::getConfig()->getGroupedClassName(
                        substr($component, 0, -1),
                        $completePath
                    );
                    if (strPos($rewriteClassName, 'ProxiBlue_GiftPromo') !== false) {
                        if($instance != $rewriteClassName
                            && strPos($instance, 'ProxiBlue_') !== false) {
                            $collection->addItem(
                                new Varien_Object(
                                    array(
                                        'path' => $completePath,
                                        'rewrite_class' => $rewriteClassName,
                                        'active_class' => $instance,
                                        'status' => 2
                                    )
                                )
                            );
                        } else {
                            $collection->addItem(
                                new Varien_Object(
                                    array(
                                        'path' => $completePath,
                                        'rewrite_class' => $rewriteClassName,
                                        'active_class' => $instance,
                                        'status' => ($instance == $rewriteClassName)
                                    )
                                )
                            );
                        }
                    }
                }
            }
        }

        return $collection;
    }


}
