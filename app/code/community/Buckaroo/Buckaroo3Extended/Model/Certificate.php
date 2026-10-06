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

class Buckaroo_Buckaroo3Extended_Model_Certificate extends Mage_Core_Model_Abstract
{
    /**
     * Marks a certificate (and private key) that is stored encrypted. Values without the marker were stored
     * by earlier versions and are still read as they are.
     */
    const ENCRYPTED_PREFIX = 'bkenc:';

    /** @var string|null */
    protected $_plainCertificate = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init('buckaroo3extended/certificate');
    }

    /**
     * Stores the certificate encrypted.
     *
     * @return $this
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        $value = $this->getData('certificate');
        $this->_plainCertificate = null;

        if (is_string($value) && $value !== '' && strpos($value, self::ENCRYPTED_PREFIX) !== 0) {
            $this->_plainCertificate = $value;
            $this->setData('certificate', self::ENCRYPTED_PREFIX . Mage::helper('core')->encrypt($value));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterSave()
    {
        parent::_afterSave();

        if ($this->_plainCertificate !== null) {
            $this->setData('certificate', $this->_plainCertificate);
            $this->_plainCertificate = null;
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $value = $this->getData('certificate');

        if (is_string($value) && strpos($value, self::ENCRYPTED_PREFIX) === 0) {
            $this->setData(
                'certificate',
                Mage::helper('core')->decrypt(substr($value, strlen(self::ENCRYPTED_PREFIX)))
            );
        }

        return $this;
    }
}
