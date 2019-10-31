<?php

class TIG_Buckaroo3Extended_Model_Certificate_Certificate extends Mage_Core_Model_Abstract
{
    /**
     * Uploads the certificate file.
     *
     * @param Varien_Object $object
     */
    // @codingStandardsIgnoreStart
    public function uploadAndImport(Varien_Object $object)
    {
        if (isset($_FILES['groups']['name']['buckaroo3extended_certificate']['fields']['certificate_upload']['value'])
            && !empty(
            $_FILES['groups']['name']['buckaroo3extended_certificate']['fields']['certificate_upload']['value']
            )
            && file_exists(
                $_FILES['groups']['tmp_name']['buckaroo3extended_certificate']['fields']['certificate_upload']['value']
            )
        ) {
            try {
                $postData = Mage::app()->getRequest()->getPost();

                //check if a certificate name is defined
                if (!isset($postData['groups']['buckaroo3extended_certificate']['fields']['certificate_name']['value'])
                    || empty(
                        $postData['groups']['buckaroo3extended_certificate']['fields']['certificate_name']['value']
                    )
                ) {
                    Mage::throwException('please enter a name for this certificate');
                }

                if (strpos(
                    $_FILES['groups']['name']['buckaroo3extended_certificate']['fields']['certificate_upload']['value'],
                    '.pem'
                ) === false) {
                    Mage::throwException('invalid certificate file uploaded');
                }

                $model      = Mage::getModel('buckaroo3extended/certificate');
                $collection = $model->getCollection()->load();
                $names      = $collection->getColumnValues('certificate_name');

                //check if chosen certificate name is already in use
                if (in_array(
                    $postData['groups']['buckaroo3extended_certificate']['fields']['certificate_name']['value'], $names
                )) {
                    Mage::throwException(
                        'The certificate name \''
                        . $postData['groups']['buckaroo3extended_certificate']['fields']['certificate_name']['value']
                        . '\' is already in use.'
                    );
                }

                $data = array(
                    'certificate'      => file_get_contents(
                        $_FILES['groups']['tmp_name']['buckaroo3extended_certificate']['fields']['certificate_upload']
                        ['value']
                    ),
                    'certificate_name' => $postData['groups']['buckaroo3extended_certificate']['fields']
                                          ['certificate_name']['value'],
                    'upload_date'      => date('Y:m:d H:i:s'),
                );
                $model->setData($data);
                $model->save();
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                return $object;
            }
        }

        return $object;
    }
    // @codingStandardsIgnoreEnd
}
