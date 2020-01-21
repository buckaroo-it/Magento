<?php
/**
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
class Buckaroo_Buckaroo3Extended_Test_Framework_Buckaroo_Test_Config extends Mage_Core_Model_Config
{
    /**
     * @var array
     */
    protected $_mockModels = array();

    /**
     * @var array
     */
    protected $_mockResourceModels = array();

    /**
     * @param string $modelClass
     * @param object $mock
     *
     * @return $this
     */
    public function setModelMock($modelClass, $mock)
    {
        $this->_mockModels[$modelClass] = $mock;
        return $this;
    }

    /**
     * @param string $modelClass
     * @param object $mock
     *
     * @return $this
     */
    public function setResourceModelMock($modelClass, $mock)
    {
        $this->_mockResourceModels[$modelClass] = $mock;
        return $this;
    }

    /**
     * @param string $modelClass
     * @param array  $constructArguments
     *
     * @return false|Mage_Core_Model_Abstract
     */
    public function getModelInstance($modelClass = '', $constructArguments = array())
    {
        $modelClass = (string) $modelClass;

        if (array_key_exists($modelClass, $this->_mockModels)) {
            return $this->_mockModels[$modelClass];
        }

        return parent::getModelInstance($modelClass, $constructArguments);
    }

    /**
     * Get resource model object by alias
     *
     * @param   string $modelClass
     * @param   array $constructArguments
     * @return  object
     */
    public function getResourceModelInstance($modelClass='', $constructArguments = array())
    {
        $modelClass = (string) $modelClass;

        if (array_key_exists($modelClass, $this->_mockResourceModels)) {
            return $this->_mockResourceModels[$modelClass];
        }

        return parent::getResourceModelInstance($modelClass, $constructArguments);
    }
}
