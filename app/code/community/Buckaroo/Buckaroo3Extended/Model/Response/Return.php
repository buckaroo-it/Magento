<?php
class Buckaroo_Buckaroo3Extended_Model_Response_Return extends Buckaroo_Buckaroo3Extended_Model_Response_Push
{
    public function processReturn()
    {
        Mage::helper('buckaroo3extended')->devLog(__METHOD__, 1, $this->_postArray);

        //check if the push is valid and if the order can be updated
        list($canProcess, $canUpdate) = $this->_canProcessPush(true);

        $this->_debugEmail .= "can the order be processed? " . $canProcess . "\ncan the order be updated? " . $canUpdate . "\n";

        if (!$canProcess) {
            $this->_verifyError();
            return false;
        }

        Mage::dispatchEvent('buckaroo3extended_return_custom_processing', array('return' => $this, 'order' => $this->getCurrentOrder(), 'post_array' => $this->_postArray));

        if ($this->getCustomResponseProcessing()) {
            return true;
        }

        $parsedResponse = $this->_parsePostResponse($this->_postArray['brq_statuscode']);

        $this->_requiredAction($parsedResponse);
    }

    /**
     * Sends the shopper away without processing the posted data.
     */
    public function rejectReturn()
    {
        $this->_verifyError();
    }

    public function customSuccess()
    {
        if ($this->getCustomResponseProcessing()) {
            $this->_success();
        }
    }

    public function customFailed()
    {
        if ($this->getCustomResponseProcessing()) {
            $this->_failed();
        }
    }
}
