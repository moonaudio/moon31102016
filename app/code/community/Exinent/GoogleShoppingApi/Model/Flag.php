<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */


/**
 * Google shopping synchronization operations flag
 *
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Flag extends Mage_Core_Model_Flag
{
    /**
     * Flag time to live in seconds
     */
    const FLAG_TTL = 72000;

    /**
     * Synchronize flag code
     *
     * @var string
     */
    protected $_flagCode = 'googleshoppingapi';

    /**
     * Lock flag
     */
    public function lock()
    {
        $this->setState(1)
            ->save();
    }

    /**
     * Check wheter flag is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        return !!$this->getState() && !$this->isExpired();
    }

    /**
     * Unlock flag
     */
    public function unlock()
    {
        $lastUpdate = $this->getLastUpdate();
        $this->loadSelf();
        $this->setState(0);
        if ($lastUpdate == $this->getLastUpdate()) {
            $this->save();
        }
    }

    /**
     * Check whether flag is unlocked by expiration
     *
     * @return bool
     */
    public function isExpired()
    {
        if (!!$this->getState() && Exinent_GoogleShoppingApi_Model_Flag::FLAG_TTL) {
            if ($this->getLastUpdate()) {
                return (time() > (strtotime($this->getLastUpdate()) + Exinent_GoogleShoppingApi_Model_Flag::FLAG_TTL));
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}
