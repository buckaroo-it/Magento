<?php

class Buckaroo_Buckaroo3Extended_Model_Certificate_Certificate extends Mage_Core_Model_Abstract
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

                $certificateName = $postData['groups']['buckaroo3extended_certificate']['fields']
                                   ['certificate_name']['value'];

                if (!is_string($certificateName) || !preg_match('/^[A-Za-z0-9 _.-]{1,15}$/', $certificateName)) {
                    Mage::throwException(
                        'The certificate name may only contain letters, numbers, spaces, dots, dashes and '
                        . 'underscores (maximum 15 characters).'
                    );
                }

                $uploadedFile = $_FILES['groups']['tmp_name']['buckaroo3extended_certificate']['fields']
                                ['certificate_upload']['value'];

                if (!preg_match(
                    '/\.pem$/i',
                    $_FILES['groups']['name']['buckaroo3extended_certificate']['fields']['certificate_upload']['value']
                ) || filesize($uploadedFile) > 16384) {
                    Mage::throwException('invalid certificate file uploaded');
                }

                //the file has to contain a private key that can be used without a passphrase
                $certificateContents = file_get_contents($uploadedFile);
                if (strpos($certificateContents, '-----BEGIN') === false
                    || openssl_pkey_get_private($certificateContents, '') === false
                ) {
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
                    'certificate'      => $certificateContents,
                    'certificate_name' => $certificateName,
                    'upload_date'      => date('Y-m-d H:i:s'),
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
