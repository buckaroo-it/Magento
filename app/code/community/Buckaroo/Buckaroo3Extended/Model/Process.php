<?php
/**  ____________  _     _ _ ________  ___  _ _  _______   ___  ___  _  _ _ ___
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */

class Buckaroo_Buckaroo3Extended_Model_Process extends Mage_Index_Model_Process
{
    protected $_isLocked = null;

    /**
     * @return string
     */
    protected function _getLockFilePath()
    {
        $varDir = Mage::getConfig()->getVarDir('locks');

        return $varDir . DS . 'buckaroo_process_' . $this->getId() . '.lock';
    }

    /**
     * Get lock file resource. An existing lock file is opened without truncating it, so the process that holds
     * the lock keeps its file intact.
     *
     * @return resource | Buckaroo_Buckaroo3Extended_Model_Process
     */
    protected function _getLockFile()
    {
        if ($this->_lockFile !== null) {
            return $this->_lockFile;
        }

        $this->_lockFile = fopen($this->_getLockFilePath(), 'c');

        return $this->_lockFile;
    }

    /**
     * Opens the lock file and takes the lock. Makes sure the locked file is still the file on disk, because the
     * process that held the lock before us removes the file when it is done.
     *
     * @param bool $block
     *
     * @return bool
     */
    protected function _acquire($block)
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->_lockFile = null;
            $handle = $this->_getLockFile();

            if (!is_resource($handle)) {
                return false;
            }

            if (!flock($handle, $block ? LOCK_EX : (LOCK_EX | LOCK_NB))) {
                fclose($handle);
                $this->_lockFile = null;

                return false;
            }

            clearstatcache(true, $this->_getLockFilePath());
            $onDisk = @stat($this->_getLockFilePath());
            $held   = fstat($handle);

            if ($onDisk && $held && $onDisk['ino'] === $held['ino']) {
                ftruncate($handle, 0);
                fwrite($handle, date('r'));
                fflush($handle);

                return true;
            }

            //the file was replaced while we were waiting; try again with the new one
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        $this->_lockFile = null;

        return false;
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     *
     * @return Buckaroo_Buckaroo3Extended_Model_Process
     */
    public function lock()
    {
        $this->_isLocked = $this->_acquire(false);

        return $this;
    }

    /**
     * Try to take the lock without blocking. Check and lock happen in one step.
     *
     * @return bool true when the lock is now held by this process
     */
    public function tryLock()
    {
        $this->_isLocked = $this->_acquire(false);

        return $this->_isLocked;
    }

    /**
     * Lock and block process
     *
     * @return Buckaroo_Buckaroo3Extended_Model_Process
     */
    public function lockAndBlock()
    {
        $this->_isLocked = $this->_acquire(true);

        return $this;
    }

    /**
     * Unlock process
     *
     * @return Buckaroo_Buckaroo3Extended_Model_Process
     */
    public function unlock()
    {
        $this->_isLocked = false;

        if (is_resource($this->_lockFile)) {
            //remove the file while we still hold the lock, then release it
            @unlink($this->_getLockFilePath());
            flock($this->_lockFile, LOCK_UN);
            fclose($this->_lockFile);
        }

        $this->_lockFile = null;

        return $this;
    }

    /**
     * Check if process is locked. A lock that is held by a process that no longer exists is released by the
     * system, so no expiry is needed.
     *
     * @return bool
     */
    public function isLocked()
    {
        if ($this->_isLocked !== null) {
            return $this->_isLocked;
        }

        $handle = fopen($this->_getLockFilePath(), 'c');
        if (!is_resource($handle)) {
            return false;
        }

        if (flock($handle, LOCK_EX | LOCK_NB)) {
            flock($handle, LOCK_UN);
            fclose($handle);

            return false;
        }

        fclose($handle);

        return true;
    }
}
