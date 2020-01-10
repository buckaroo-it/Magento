<?php
/**  ____________  _     _ _ ________  ___  _ _  _______   ___  ___  _  _ _ ___
 *   \_ _/ \_ _/ \| |   |_| \ \_ _/  \| _ || \ |/  \_ _/  / __\| _ |/ \| | | _ \
 *    | | | | | ' | |_  | |   || | '_/|   /|   | '_/| |  | |_ \|   / | | | | __/
 *    |_|\_/|_|_|_|___| |_|_\_||_|\__/|_\_\|_\_|\__/|_|   \___/|_\_\\_/|___|_|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class TIG_Buckaroo3Extended_Model_Process extends Mage_Index_Model_Process
{
    protected $_isLocked = null;

    /**
     * Get lock file resource
     *
     * @return resource | TIG_Buckaroo3Extended_Model_Process
     */
    protected function _getLockFile()
    {
        if ($this->_lockFile !== null) {
            return $this->_lockFile;
        }

        $varDir = Mage::getConfig()->getVarDir('locks');
        $file = $varDir . DS . 'buckaroo_process_' . $this->getId() . '.lock';

        if (is_file($file)) {
            if($this->_lockIsExpired()){
                unlink($file);//remove file
                $this->_lockFile = fopen($file, 'x');//create new lock file
            }else{
                $this->_lockFile = fopen($file, 'w');
            }
        } else {
            $this->_lockFile = fopen($file, 'x');
        }

        fwrite($this->_lockFile, date('r'));

        return $this->_lockFile;
    }

    /**
     * Lock process without blocking.
     * This method allow protect multiple process running and fast lock validation.
     *
     * @return TIG_Buckaroo3Extended_Model_Process
     */
    public function lock()
    {
        $this->_isLocked = true;

        flock($this->_getLockFile(), LOCK_EX | LOCK_NB);

        return $this;
    }

    /**
     * Lock and block process
     *
     * @return TIG_Buckaroo3Extended_Model_Process
     */
    public function lockAndBlock()
    {
        $this->_isLocked = true;
        $file = $this->_getLockFile();

        flock($this->_getLockFile(), LOCK_EX);

        return $this;
    }

    /**
     * Unlock process
     *
     * @return TIG_Buckaroo3Extended_Model_Process
     */
    public function unlock()
    {
        $this->_isLocked = false;
        $file = $this->_getLockFile();

        flock($file, LOCK_UN);

        //remove lockfile
        $varDir   = Mage::getConfig()->getVarDir('locks');
        $lockFile = $varDir . DS . 'buckaroo_process_' . $this->getId() . '.lock';
        unlink($lockFile);

        return $this;
    }

    /**
     * Check if process is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        if ($this->_isLocked !== null) {
            return $this->_isLocked;
        }

        $fp = $this->_getLockFile();
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);
            return false;
        }

        //if the lock exists and exists for longer then 5minutes then remove lock & return false
        if($this->_lockIsExpired()){
            $varDir   = Mage::getConfig()->getVarDir('locks');
            $lockFile = $varDir . DS . 'buckaroo_process_' . $this->getId() . '.lock';
            unlink($lockFile);

            $this->_getLockFile();//create new lock file
            return false;
        }

        return true;
    }

    /**
     * Checks if the lock has expired
     *
     * @return bool
     */
    protected function _lockIsExpired()
    {
        $varDir     = Mage::getConfig()->getVarDir('locks');
        $file       = $varDir . DS . 'buckaroo_process_'.$this->getId().'.lock';

        if(!is_file($file)){
            $fp = fopen($file, 'x');
            fwrite($fp, date('r'));
            fclose($fp);
            return false;
        }


        $fiveMinAgo = time() - 300;//300
        $contents   = file_get_contents($file);
        $time       = strtotime($contents);
        $debug      = 'current contents: '.$contents . "\n"
                    . 'contents in timestamp: '.$time . "\n"
                    . '5 minutes ago in timestamp: '.$fiveMinAgo;

        if($time <= $fiveMinAgo){
            $fp = fopen($file, 'w');
            flock($fp, LOCK_UN);
            return true;
        }

        return false;
    }
}
