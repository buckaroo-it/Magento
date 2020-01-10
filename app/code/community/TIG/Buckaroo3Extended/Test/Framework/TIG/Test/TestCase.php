<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Test_Framework_TIG_Test_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Resets and restarts Magento.
     */
    public static function resetMagento()
    {
        // @codingStandardsIgnoreLine
        Mage::reset();

        Mage::setIsDeveloperMode(true);
        Mage::app(
            'admin',
            'store',
            array(
                'config_model' => 'TIG_Buckaroo3Extended_Test_Framework_TIG_Test_Config'
            )
        )->setResponse(new TIG_Buckaroo3Extended_Test_Framework_TIG_Test_Http_Response());

        $handler = set_error_handler(
            function () {
            }
        );

        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) use ($handler) {
                if (E_WARNING === $errno
                    && 0 === strpos($errstr, 'include(')
                    && substr($errfile, -19) == 'Varien/Autoload.php'
                ) {
                    return null;
                }

                // @codingStandardsIgnoreLine
                return call_user_func(
                    $handler, $errno, $errstr, $errfile, $errline
                );
            }
        );
    }

    public function prepareFrontendDispatch()
    {
        $store = Mage::app()->getDefaultStoreView();
        $store->setConfig('web/url/redirect_to_base', false);
        $store->setConfig('web/url/use_store', false);
        $store->setConfig('advanced/modules_disable_output/Enterprise_Banner', true);

        Mage::app()->setCurrentStore($store->getCode());

        $this->registerMockSessions();
    }

    public function registerMockSessions($modules = null)
    {
        if (!is_array($modules)) {
            $modules = array('core', 'customer', 'checkout', 'catalog', 'reports');
        }

        if (!in_array('core', $modules)) {
            array_unshift($modules, 'core');
        }

        foreach ($modules as $module) {
            $class = "$module/session";
            $sessionMock = $this->getMockBuilder(Mage::getConfig()->getModelClassName($class))
                ->disableOriginalConstructor()
                ->getMock();
            $sessionMock->expects($this->any())
                        ->method('start')
                        ->will($this->returnSelf());
            $sessionMock->expects($this->any())
                        ->method('init')
                        ->will($this->returnSelf());
            $sessionMock->expects($this->any())
                        ->method('getMessages')
                        ->will($this->returnValue(Mage::getModel('core/message_collection')));
            $sessionMock->expects($this->any())
                        ->method('getSessionIdQueryParam')
                        ->will($this->returnValue(Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM));
            $this->setSingletonMock($class, $sessionMock);
            $this->setModelMock($class, $sessionMock);
        }

        $cookieMock = $this->getMockBuilder('Mage_Core_Model_Cookie')->getMock();
        $cookieMock->expects($this->any())
                   ->method('get')
                   ->will($this->returnValue(serialize('dummy')));
        Mage::unregister('_singleton/core/cookie');
        Mage::register('_singleton/core/cookie', $cookieMock);

        // mock visitor log observer
        $logVisitorMock = $this->getMockBuilder('Mage_Log_Model_Visitor')->getMock();
        $this->setModelMock('log/visitor', $logVisitorMock);

        /**
         * Fix enterprise catalog permissions issue
         */
        $factoryName = 'enterprise_catalogpermissions/permission_index';
        $className = Mage::getConfig()->getModelClassName($factoryName);
        if (class_exists($className)) {
            $mockPermissions = $this->getMockBuilder($className)->getMock();
            $mockPermissions->expects($this->any())
                            ->method('getIndexForCategory')
                            ->withAnyParameters()
                            ->will($this->returnValue(array()));

            $this->setSingletonMock($factoryName, $mockPermissions);
        }
    }

    /**
     * @param string $modelClass
     * @param object $mock
     *
     * @return TIG_Test_TestCase
     */
    public function setModelMock($modelClass, $mock)
    {
        $this->getConfig()->setModelMock($modelClass, $mock);

        return $this;
    }
    /**
     * @param string $modelClass
     * @param object $mock
     *
     * @return TIG_Test_TestCase
     */
    public function setResourceModelMock($modelClass, $mock)
    {
        $this->getConfig()->setResourceModelMock($modelClass, $mock);

        return $this;
    }

    /**
     * @param string $modelClass
     * @param object $mock
     *
     * @return TIG_Test_TestCase
     */
    public function setSingletonMock($modelClass, $mock)
    {
        $registryKey = '_singleton/' . $modelClass;

        Mage::unregister($registryKey);
        Mage::register($registryKey, $mock);

        return $this;
    }

    /**
     * @param $modelClass
     *
     * @return mixed
     */
    public function getSingletonMock($modelClass)
    {
        $registryKey = '_singleton/' . $modelClass;

        return Mage::registry($registryKey);
    }

    /**
     * @param string $resourceModelClass
     * @param object $mock
     *
     * @return TIG_Test_TestCase
     */
    public function setResourceSingletonMock($resourceModelClass, $mock)
    {
        $registryKey = '_resource_singleton/' . $resourceModelClass;

        Mage::unregister($registryKey);
        Mage::register($registryKey, $mock);

        return $this;
    }

    /**
     * @param string $helperClass
     * @param object $mock
     *
     * @return TIG_Test_TestCase
     */
    public function setHelperMock($helperClass, $mock)
    {
        $registryKey = '_helper/' . $helperClass;

        Mage::unregister($registryKey);
        Mage::register($registryKey, $mock);

        return $this;
    }

    /**
     * @return TIG_Test_Config
     */
    public function getConfig()
    {
        return Mage::getConfig();
    }

    /**
     * Returns the instance. Should be overridden.
     *
     * @return null
     */
    protected function _getInstance()
    {
        return null;
    }

    /**
     * Sets a protected property to the provided value.
     *
     * @param      $property
     * @param      $value
     * @param null $instance
     *
     * @return $this
     */
    public function setProperty($property, $value, $instance = null)
    {
        if ($instance === null) {
            $instance = $this->_getInstance();
        }

        $reflection = new ReflectionObject($instance);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($instance, $value);

        return $this;
    }

    /**
     * Updates a specific key.
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setRegistryKey($key, $value)
    {
        Mage::unregister($key);
        Mage::register($key, $value);

        return $this;
    }

    /**
     * Call public/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
