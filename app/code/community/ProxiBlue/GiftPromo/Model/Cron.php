<?php

/**
 * Cron functions
 *
 * @category  ProxiBlue
 * @package   GiftPromo
 * @author    Lucas van Staden <sales@proxiblue.com.au>
 * @copyright 2016 Lucas van Staden (ProxiBlue)
 * @license   http://www.proxiblue.com.au/eula EULA
 * @link      http://www.proxiblue.com.au
 */
class ProxiBlue_GiftPromo_Model_Cron
{

    protected $_helper = null;

    /**
     * Clean cache of stale cache entries
     *
     * @return void
     */
    public static function cleanCache()
    {
        $tempDir = sys_get_temp_dir() . "/";
        $filePointer = fopen(
            $tempDir . "giftpromo_cleancache.lock",
            "w+"
        );
        try {
            if (flock(
                $filePointer,
                LOCK_EX | LOCK_NB
            )) {
                // clear out any stale cache entries
                $useCache = Mage::app()->useCache('giftpromo_product_valid');
                if ($useCache) {
                    $cacheModel = Mage::app()->getCache();
                    // clear out any old cache for this.
                    /** @noinspection PhpUndefinedMethodInspection */
                    self::getHelper()->debug("Giftpromo - clean cache cron");
                    $cacheModel->clean(
                        Zend_Cache::CLEANING_MODE_OLD,
                        array('GIFTPROMO_PRODUCT_VALID')
                    );
                }
                flock(
                    $filePointer,
                    LOCK_UN
                );
                unlink($tempDir . "giftpromo_cleancache.lock");
            } else {
                self::getHelper()->debug(
                    "Could not execute cron for clean cache - file lock '{$filePointer}' is in place, job may be running."
                );
            }
        } catch (Exception $e) {
            flock(
                $filePointer,
                LOCK_UN
            );
            unlink($tempDir . "giftpromo_cleancache.lock");
            mage::logException($e);
            self::getHelper()->debug($e->getMessage());
            mage::throwException($e->getMessage());
        }
    }

    public static function getHelper()
    {
        return mage::helper('giftpromo');
    }

}
