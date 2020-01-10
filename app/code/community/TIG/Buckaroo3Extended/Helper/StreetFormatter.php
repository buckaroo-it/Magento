<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Helper_StreetFormatter extends Mage_Core_Helper_Abstract
{
    const STREET_SPLIT_NAME_FROM_NUMBER = '/^(?P<street>\d*[\wäöüßÀ-ÖØ-öø-ÿĀ-Ž\d \'\-\.]+)[,\s]+(?P<number>\d+)\s*(?P<addition>[\wäöüß\d\-\/]*)$/i';

    const STREET_SPLIT_NUMBER_FROM_NAME = '/^(?P<number>\d+)\s*(?P<street>[\wäöüßÀ-ÖØ-öø-ÿĀ-Ž\d \'\-\.]*)$/i';

    const LEGACY_STREET_SPLIT = '#^(.*?)([0-9]+)(.*)#s';

    /**
     * @param string|array $street
     *
     * @return array
     */
    public function format($street)
    {
        if (is_array($street)) {
            $street = trim(implode(' ', $street));
        }

        $result = $this->extractHousenumber($street);

        if (!$result) {
            $result = $this->extractStreetFromNumber($street);
        }

        if (!$result) {
            $result = $this->legacyFormat($street);
        }

        $formattedResult = $this->formatResult($result);

        return $formattedResult;
    }

    /**
     * @param string $street
     *
     * @return bool|array
     */
    private function extractHousenumber($street)
    {
        $match = preg_match(self::STREET_SPLIT_NAME_FROM_NUMBER, $street, $result);

        if (!$match) {
            return false;
        }

        return $result;
    }

    /**
     * @param string $street
     *
     * @return bool|array
     */
    private function extractStreetFromNumber($street)
    {
        $match = preg_match(self::STREET_SPLIT_NUMBER_FROM_NAME, $street, $result);

        if (!$match) {
            return false;
        }

        return $result;
    }

    /**
     * @param array $result
     *
     * @return array
     */
    private function formatResult($result)
    {
        $format = array(
            'street'          => '',
            'house_number'    => '',
            'number_addition' => ''
        );

        if (isset($result['street'])) {
            $format['street'] = trim($result['street']);
        }

        if (isset($result['number'])) {
            $format['house_number'] = trim($result['number']);
        }

        if (isset($result['addition'])) {
            $format['number_addition'] = trim($result['addition']);
        }

        return $format;
    }

    /**
     * @param string $street
     *
     * @return array
     */
    private function legacyFormat($street)
    {
        $format = array(
            'street'   => $street,
            'number'   => '',
            'addition' => ''
        );

        $match = preg_match(self::LEGACY_STREET_SPLIT, $street, $matches);

        if ($match) {
            $format = $this->legacyFormatResult($matches);
        }

        return $format;
    }

    /**
     * @param array $matches
     *
     * @return array
     */
    private function legacyFormatResult($matches)
    {
        $format = array(
            'street'   => trim($matches[3]),
            'number'   => trim($matches[2]),
            'addition' => '',
        );

        if (!('' == $matches[1])) {
            $format['street']          = trim($matches[1]);
            $format['addition'] = trim($matches[3]);
        }

        return $format;
    }
}
