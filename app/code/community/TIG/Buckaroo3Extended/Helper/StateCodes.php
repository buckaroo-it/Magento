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
 * @copyright   Copyright (c) TIG B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_Buckaroo3Extended_Helper_StateCodes extends Mage_Core_Helper_Abstract
{

    /**
     * Contains all state codes and the relevant full names for the countries paypal supports (or requires) state codes
     * for.
     *
     * @var array
     */
    protected $_codes = array(
        'CA' => array(
            'AB' => array('Alberta'),
            'BC' => array('British Columbia'),
            'MB' => array('Manitoba'),
            'NB' => array('New Brunswick'),
            'NL' => array('Newfoundland and Labrador', 'Newfoundland', 'Labrador'),
            'NT' => array('Northwest Territories'),
            'NS' => array('Nova Scotia'),
            'NU' => array('Nunavut'),
            'ON' => array('Ontario'),
            'PE' => array('Prince Edward Island'),
            'QC' => array('Quebec'),
            'SK' => array('Saskatchewan'),
            'YT' => array('Yukon Territory', 'Yukon'),
        ),
        'IT' => array(
            'AG' => array('Agrigento'),
            'AL' => array('Alessandria'),
            'AN' => array('Ancona'),
            'AO' => array('Aosta'),
            'AR' => array('Arezzo'),
            'AP' => array('Ascoli Piceno'),
            'AT' => array('Asti'),
            'AV' => array('Avellino'),
            'BA' => array('Bari'),
            'BL' => array('Belluno'),
            'BN' => array('Benevento'),
            'BG' => array('Bergamo'),
            'BI' => array('Biella'),
            'BO' => array('Bologna'),
            'BZ' => array('Bolzano'),
            'BS' => array('Brescia'),
            'BR' => array('Brindisi'),
            'CA' => array('Cagliari'),
            'CL' => array('Caltanissetta'),
            'CB' => array('Campobasso'),
            'CE' => array('Caserta'),
            'CT' => array('Catania'),
            'CZ' => array('Catanzaro'),
            'CH' => array('Chieti'),
            'CO' => array('Como'),
            'CS' => array('Cosenza'),
            'CR' => array('Cremona'),
            'KR' => array('Crotone'),
            'CN' => array('Cuneo'),
            'EN' => array('Enna'),
            'FE' => array('Ferrarav'),
            'FI' => array('Firenze'),
            'FG' => array('Foggia'),
            'FO' => array('Forli-Cesena'),
            'FR' => array('Frosinone'),
            'GE' => array('Genova'),
            'GO' => array('Gorizia'),
            'GR' => array('Grosseto'),
            'IM' => array('Imperia'),
            'IS' => array('Isernia'),
            'SP' => array('La Spezia'),
            'AQ' => array('L’Aquila'),
            'LT' => array('Latina'),
            'LE' => array('Lecce'),
            'LC' => array('Lecco'),
            'LI' => array('Livorno'),
            'LO' => array('Lodi'),
            'LU' => array('Lucca'),
            'MC' => array('Macerata'),
            'MN' => array('Mantova'),
            'MS' => array('Massa-Carrara'),
            'MT' => array('Matera'),
            'ME' => array('Messina'),
            'MI' => array('Milano'),
            'MO' => array('Modena'),
            'MB' => array('Monza e Brianza'),
            'NA' => array('Napoli'),
            'NO' => array('Novara'),
            'NU' => array('Nuoro'),
            'OR' => array('Oristano'),
            'PD' => array('Padova'),
            'PA' => array('Palermo'),
            'PR' => array('Parma'),
            'PV' => array('Pavia'),
            'PG' => array('Perugia'),
            'PS' => array('Pesaro'),
            'PE' => array('Pescara'),
            'PC' => array('Piacenza'),
            'PI' => array('Pisa'),
            'PT' => array('Pistoia'),
            'PN' => array('Pordenone'),
            'PZ' => array('Potenza'),
            'PO' => array('Prato'),
            'RG' => array('Ragusa'),
            'RA' => array('Ravenna'),
            'RC' => array('Reggio Calabria'),
            'RE' => array('Reggio Emilia'),
            'RI' => array('Rieti'),
            'RN' => array('Rimini'),
            'RM' => array('Roma'),
            'RO' => array('Rovigo'),
            'SA' => array('Salerno'),
            'SS' => array('Sassari'),
            'SV' => array('Savona'),
            'SI' => array('Siena'),
            'SR' => array('Siracusa'),
            'SO' => array('Sondrio'),
            'TA' => array('Taranto'),
            'TE' => array('Teramo'),
            'TR' => array('Terni'),
            'TO' => array('Torino'),
            'TP' => array('Trapani'),
            'TN' => array('Trento'),
            'TV' => array('Treviso'),
            'TS' => array('Trieste'),
            'UD' => array('Udine'),
            'VA' => array('Varese'),
            'VE' => array('Venezia'),
            'VB' => array('Verbania-Cusio-Ossola'),
            'VC' => array('Vercelli'),
            'VR' => array('Verona'),
            'VV' => array('Vibo Valentia'),
            'VI' => array('Vicenza'),
            'VT' => array('Viterbo'),
        ),
        'NL' => array(
            'DR' => array('Drenthe'),
            'FL' => array('Flevoland'),
            'FR' => array('Friesland'),
            'GE' => array('Gelderland'),
            'GR' => array('Groningen'),
            'LI' => array('Limburg'),
            'NB' => array('Noord-Brabant'),
            'NH' => array('Noord-Holland'),
            'OV' => array('Overijssel'),
            'UT' => array('Utrecht'),
            'ZE' => array('Zeeland'),
            'ZH' => array('Zuid-Holland'),
        ),
        'US' => array(
            'AL' => array('Alabama'),
            'AK' => array('Alaska'),
            'AS' => array('American Samoa'),
            'AZ' => array('Arizona'),
            'AR' => array('Arkansas'),
            'AE' => array(
                'Armed Forces Africa',
                'Armed Forces Europe',
                'Armed Forces Canada',
                'Armed Forces Middle East',
            ),
            'AA' => array('Armed Forces Americas'),
            'AP' => array('Armed Forces Pacific'),
            'CA' => array('California'),
            'CO' => array('Colorado'),
            'CT' => array('Connecticut'),
            'DE' => array('Delaware'),
            'DC' => array('District of Columbia'),
            'FM' => array('Federated States Of Micronesia'),
            'FL' => array('Florida'),
            'GA' => array('Georgia'),
            'GU' => array('Guam'),
            'HI' => array('Hawaii'),
            'ID' => array('Idaho'),
            'IL' => array('Illinois'),
            'IN' => array('Indiana'),
            'IA' => array('Iowa'),
            'KS' => array('Kansas'),
            'KY' => array('Kentucky'),
            'LA' => array('Louisiana'),
            'ME' => array('Maine'),
            'MH' => array('Marshall Islands'),
            'MD' => array('Maryland'),
            'MA' => array('Massachusetts'),
            'MI' => array('Michigan'),
            'MN' => array('Minnesota'),
            'MS' => array('Mississippi'),
            'MO' => array('Missouri'),
            'MT' => array('Montana'),
            'NE' => array('Nebraska'),
            'NV' => array('Nevada'),
            'NH' => array('New Hampshire'),
            'NJ' => array('New Jersey'),
            'NM' => array('New Mexico'),
            'NY' => array('New York'),
            'NC' => array('North Carolina'),
            'ND' => array('North Dakota'),
            'MP' => array('Northern Mariana Islands'),
            'OH' => array('Ohio'),
            'OK' => array('Oklahoma'),
            'OR' => array('Oregon'),
            'PW' => array('Palau'),
            'PA' => array('Pennsylvania'),
            'PR' => array('Puerto Rico'),
            'RI' => array('Rhode Island'),
            'SC' => array('South Carolina'),
            'SD' => array('South Dakota'),
            'TN' => array('Tennessee'),
            'TX' => array('Texas'),
            'UT' => array('Utah'),
            'VT' => array('Vermont'),
            'VI' => array('Virgin Islands'),
            'VA' => array('Virginia'),
            'WA' => array('Washington'),
            'WV' => array('West Virginia'),
            'WI' => array('Wisconsin'),
            'WY' => array('Wyoming'),
        ),
    );

    /**
     * Returns array of values based on country & state codes, or everything if only country code given
     *
     * @param string $countryCode
     * @param null $stateCode
     *
     * @return bool|array
     */
    public function getValuesFromCodes($countryCode = null, $stateCode = null)
    {
        // We need a countryCode + stateCode and for it to exist in _codes
        if (!$countryCode || !$stateCode || !isset($this->_codes[$countryCode])) {
            return false;
        }

        // If statecode is both given and exists in _codes, return its array value
        if (isset($this->_codes[$countryCode][$stateCode])) {
            return $this->_codes[$countryCode][$stateCode];
        }

        // Nothing found, return false instead
        return false;
    }

    /**
     * Returns code string based on country code and value, or false if not found. Also returns an array if
     *
     * @param null $countryCode
     * @param null $value
     *
     * @return false|string
     */
    public function getCodeFromValue($countryCode = null, $value = null)
    {
        // We need a countryCode + value and for it to exist in _codes
        if (!$countryCode || !$value || !isset($this->_codes[$countryCode])) {
            return false;
        }

        // Loop through and do an in_array search (some state codes have multiple names)
        foreach ($this->_codes[$countryCode] as $stateCode => $stateValues) {
            if (in_array($value, $stateValues)) {
                // As soon as we find one, return the code
                return $stateCode;
            }
        }

        // Nothing found, return false instead
        return false;
    }

    /**
     * Returns all codes or all codes by countryCode. False if countryCode given but non-existant
     *
     * @param null $countryCode
     *
     * @return array|bool
     */
    public function getCodes($countryCode = null)
    {
        if (!$countryCode) {
            return $this->_codes;
        }

        if (isset($this->_codes[$countryCode])) {
            return $this->_codes[$countryCode];
        }

        // Nothing found based on countryCode but it was given, return false instead
        return false;
    }

}
