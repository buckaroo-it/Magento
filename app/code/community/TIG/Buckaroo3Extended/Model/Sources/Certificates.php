<?php
class TIG_Buckaroo3Extended_Model_Sources_Certificates
{
    public function toOptionArray()
    {
        $collection = Mage::getResourceModel('buckaroo3extended/certificate_collection');

        if ($collection->getSize() < 1) {
            $label = Mage::helper('buckaroo3extended')->__('You have not yet uploaded any certificate files.');

            $array = array(
                array(
                    'label' => $label,
                    'value' => '',
                ),
            );

            return $array;
        }

        $array = array(
            array(
                'label' => Mage::helper('buckaroo3extended')->__('No certificate selected.'),
                'value' => '',
            ),
        );

        foreach ($collection->getItems() as $certificate) {
            $array[] = array(
                'value' => $certificate->getCertificateId(),
                'label' => $certificate->getCertificateName() . ' (' . $certificate->getUploadDate() . ')',
            );
        }

        return $array;
    }
}
