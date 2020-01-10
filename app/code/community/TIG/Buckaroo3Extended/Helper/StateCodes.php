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
        'AR' => array(
            'CIUDAD AUTÓNOMA DE BUENOS AIRES' => array('Buenos Aires (Ciudad)'),
            'BUENOS AIRES' => array('Buenos Aires (Provincia)'),
            'CATAMARCA' => array('Catamarca'),
            'CHACO' => array('Chaco'),
            'CHUBUT' => array('Chubut'),
            'CORRIENTES' => array('Corrientes'),
            'CÓRDOBA' => array('Córdoba'),
            'ENTRE RÍOS' => array('Entre Ríos'),
            'FORMOSA' => array('Formosa'),
            'JUJUY' => array('Jujuy'),
            'LA PAMPA' => array('La Pampa'),
            'LA RIOJA' => array('La Rioja'),
            'MENDOZA' => array('Mendoza'),
            'MISIONES' => array('Misiones'),
            'NEUQUÉN' => array('Neuquén'),
            'RÍO NEGRO' => array('Río Negro'),
            'SALTA' => array('Salta'),
            'SAN JUAN' => array('San Juan'),
            'SAN LUIS' => array('San Luis'),
            'SANTA CRUZ' => array('Santa Cruz'),
            'SANTA FE' => array('Santa Fe'),
            'SANTIAGO DEL ESTERO' => array('Santiago del Estero'),
            'TIERRA DEL FUEGO' => array('Tierra del Fuego'),
            'TUCUMÁN' => array('Tucumán'),
        ),
        'BR' => array(
            'AC' => array('Acre'),
            'AL' => array('Alagoas'),
            'AP' => array('Amapá'),
            'AM' => array('Amazonas'),
            'BA' => array('Bahia'),
            'CE' => array('Ceará'),
            'DF' => array('Distrito Federal'),
            'ES' => array('Espírito Santo'),
            'GO' => array('Goiás'),
            'MA' => array('Maranhão'),
            'MT' => array('Mato Grosso'),
            'MS' => array('Mato Grosso do Sul'),
            'MG' => array('Minas Gerais'),
            'PR' => array('Paraná'),
            'PB' => array('Paraíba'),
            'PA' => array('Pará'),
            'PE' => array('Pernambuco'),
            'PI' => array('Piauí'),
            'RN' => array('Rio Grande do Norte'),
            'RS' => array('Rio Grande do Sul'),
            'RJ' => array('Rio de Janeiro'),
            'RO' => array('Rondônia'),
            'RR' => array('Roraima'),
            'SC' => array('Santa Catarina'),
            'SE' => array('Sergipe'),
            'SP' => array('São Paulo'),
            'TO' => array('Tocantins'),
        ),
        'IN' => array(
            'Andaman and Nicobar Islands' => array('Andaman and Nicobar Islands'),
            'Andhra Pradesh' => array('Andhra Pradesh'),
            'APO' => array('Army Post Office'),
            'Arunachal Pradesh' => array('Arunachal Pradesh'),
            'Assam' => array('Assam'),
            'Bihar' => array('Bihar'),
            'Chandigarh' => array('Chandigarh'),
            'Chhattisgarh' => array('Chhattisgarh'),
            'Dadra and Nagar Haveli' => array('Dadra and Nagar Haveli'),
            'Daman and Diu' => array('Daman and Diu'),
            'Delhi (NCT)' => array('Delhi'),
            'Goa' => array('Goa'),
            'Gujarat' => array('Gujarat'),
            'Haryana' => array('Haryana'),
            'Himachal Pradesh' => array('Himachal Pradesh'),
            'Jammu and Kashmir' => array('Jammu and Kashmir'),
            'Jharkhand' => array('Jharkhand'),
            'Karnataka' => array('Karnataka'),
            'Kerala' => array('Kerala'),
            'Lakshadweep' => array('Lakshadweep'),
            'Madhya Pradesh' => array('Madhya Pradesh'),
            'Maharashtra' => array('Maharashtra'),
            'Manipur' => array('Manipur'),
            'Meghalaya' => array('Meghalaya'),
            'Mizoram' => array('Mizoram'),
            'Nagaland' => array('Nagaland'),
            'Odisha' => array('Odisha'),
            'Puducherry' => array('Puducherry'),
            'Punjab' => array('Punjab'),
            'Rajasthan' => array('Rajasthan'),
            'Sikkim' => array('Sikkim'),
            'Tamil Nadu' => array('Tamil Nadu'),
            'Telangana' => array('Telangana'),
            'Tripura' => array('Tripura'),
            'Uttar Pradesh' => array('Uttar Pradesh'),
            'Uttarakhand' => array('Uttarakhand'),
            'West Bengal' => array('West Bengal'),
        ),
        'ID' => array(
            'ID-BA' => array('Bali'),
            'ID-BB' => array('Bangka Belitung'),
            'ID-BT' => array('Banten'),
            'ID-BE' => array('Bengkulu'),
            'ID-YO' => array('DI Yogyakarta'),
            'ID-JK' => array('DKI Jakarta'),
            'ID-GO' => array('Gorontalo'),
            'ID-JA' => array('Jambi'),
            'ID-JB' => array('Jawa Barat'),
            'ID-JT' => array('Jawa Tengah'),
            'ID-JI' => array('Jawa Timur'),
            'ID-KB' => array('Kalimantan Barat'),
            'ID-KS' => array('Kalimantan Selatan'),
            'ID-KT' => array('Kalimantan Tengah'),
            'ID-KI' => array('Kalimantan Timur'),
            'ID-KU' => array('Kalimantan Utara'),
            'ID-KR' => array('Kepulauan Riau'),
            'ID-LA' => array('Lampung'),
            'ID-MA' => array('Maluku'),
            'ID-MU' => array('Maluku Utara'),
            'ID-AC' => array('Nanggroe Aceh Darussalam'),
            'ID-NB' => array('Nusa Tenggara Barat'),
            'ID-NT' => array('Nusa Tenggara Timur'),
            'ID-PA' => array('Papua'),
            'ID-PB' => array('Papua Barat'),
            'ID-RI' => array('Riau'),
            'ID-SR' => array('Sulawesi Barat'),
            'ID-SN' => array('Sulawesi Selatan'),
            'ID-ST' => array('Sulawesi Tengah'),
            'ID-SG' => array('Sulawesi Tenggara'),
            'ID-SA' => array('Sulawesi Utara'),
            'ID-SB' => array('Sumatera Barat'),
            'ID-SS' => array('Sumatera Selatan'),
            'ID-SU' => array('Sumatera Utara'),
        ),
        'JP' => array(
            'AICHI-KEN' => array('Aichi'),
            'AKITA-KEN' => array('Akita'),
            'AOMORI-KEN' => array('Aomori'),
            'CHIBA-KEN' => array('Chiba'),
            'EHIME-KEN' => array('Ehime'),
            'FUKUI-KEN' => array('Fukui'),
            'FUKUOKA-KEN' => array('Fukuoka'),
            'FUKUSHIMA-KEN' => array('Fukushima'),
            'GIFU-KEN' => array('Gifu'),
            'GUNMA-KEN' => array('Gunma'),
            'HIROSHIMA-KEN' => array('Hiroshima'),
            'HOKKAIDO' => array('Hokkaido'),
            'HYOGO-KEN' => array('Hyogo'),
            'IBARAKI-KEN' => array('Ibaraki'),
            'ISHIKAWA-KEN' => array('Ishikawa'),
            'IWATE-KEN' => array('Iwate'),
            'KAGAWA-KEN' => array('Kagawa'),
            'KAGOSHIMA-KEN' => array('Kagoshima'),
            'KANAGAWA-KEN' => array('Kanagawa'),
            'KOCHI-KEN' => array('Kochi'),
            'KUMAMOTO-KEN' => array('Kumamoto'),
            'KYOTO-FU' => array('Kyoto'),
            'MIE-KEN' => array('Mie'),
            'MIYAGI-KEN' => array('Miyagi'),
            'MIYAZAKI-KEN' => array('Miyazaki'),
            'NAGANO-KEN' => array('Nagano'),
            'NAGASAKI-KEN' => array('Nagasaki'),
            'NARA-KEN' => array('Nara'),
            'NIIGATA-KEN' => array('Niigata'),
            'OITA-KEN' => array('Oita'),
            'OKAYAMA-KEN' => array('Okayama'),
            'OKINAWA-KEN' => array('Okinawa'),
            'OSAKA-FU' => array('Osaka'),
            'SAGA-KEN' => array('Saga'),
            'SAITAMA-KEN' => array('Saitama'),
            'SHIGA-KEN' => array('Shiga'),
            'SHIMANE-KEN' => array('Shimane'),
            'SHIZUOKA-KEN' => array('Shizuoka'),
            'TOCHIGI-KEN' => array('Tochigi'),
            'TOKUSHIMA-KEN' => array('Tokushima'),
            'TOKYO-TO' => array('Tokyo'),
            'TOTTORI-KEN' => array('Tottori'),
            'TOYAMA-KEN' => array('Toyama'),
            'WAKAYAMA-KEN' => array('Wakayama'),
            'YAMAGATA-KEN' => array('Yamagata'),
            'YAMAGUCHI-KEN' => array('Yamaguchi'),
            'YAMANASHI-KEN' => array('Yamanashi'),
        ),
        'MX' => array(
            'AGS' => array('Aguascalientes'),
            'BC' => array('Baja California'),
            'BCS' => array('Baja California Sur'),
            'CAMP' => array('Campeche'),
            'CHIS' => array('Chiapas'),
            'CHIH' => array('Chihuahua'),
            'CDMX' => array('Ciudad de México'),
            'COAH' => array('Coahuila'),
            'COL' => array('Colima'),
            'DF' => array('Distrito Federal'),
            'DGO' => array('Durango'),
            'MEX' => array('Estado de México'),
            'GTO' => array('Guanajuato'),
            'GRO' => array('Guerrero'),
            'HGO' => array('Hidalgo'),
            'JAL' => array('Jalisco'),
            'MICH' => array('Michoacán'),
            'MOR' => array('Morelos'),
            'NAY' => array('Nayarit'),
            'NL' => array('Nuevo León'),
            'OAX' => array('Oaxaca'),
            'PUE' => array('Puebla'),
            'QRO' => array('Querétaro'),
            'Q ROO' => array('Quintana Roo'),
            'SLP' => array('San Luis Potosí'),
            'SIN' => array('Sinaloa'),
            'SON' => array('Sonora'),
            'TAB' => array('Tabasco'),
            'TAMPS' => array('Tamaulipas'),
            'TLAX' => array('Tlaxcala'),
            'VER' => array('Veracruz'),
            'YUC' => array('Yucatán'),
            'ZAC' => array('Zacatecas'),
        ),
        'CN' => array(
            'CN-AH' => array('Anhui Sheng'),
            'CN-BJ' => array('Beijing Shi'),
            'CN-CQ' => array('Chongqing Shi'),
            'CN-FJ' => array('Fujian Sheng'),
            'CN-GD' => array('Guangdong Sheng'),
            'CN-GS' => array('Gansu Sheng'),
            'CN-GX' => array('Guangxi Zhuangzu Zizhiqu'),
            'CN-GZ' => array('Guizhou Sheng'),
            'CN-HA' => array('Henan Sheng'),
            'CN-HB' => array('Hubei Sheng'),
            'CN-HE' => array('Hebei Sheng'),
            'CN-HI' => array('Hainan Sheng'),
            'CN-HK' => array('Xianggang Tebiexingzhengqu',
                'Hong Kong SAR'),
            'CN-HL' => array('Heilongjiang Sheng'),
            'CN-HN' => array('Hunan Sheng'),
            'CN-JL' => array('Jilin Sheng'),
            'CN-JS' => array('Jiangsu Sheng'),
            'CN-JX' => array('Jiangxi Sheng'),
            'CN-LN' => array('Liaoning Sheng'),
            'CN-MO' => array('Aomen Tebiexingzhengqu',
                'Macao SAR',
                'Macau SAR'),
            'CN-NM' => array('Nei Mongol Zizhiqu'),
            'CN-NX' => array('Ningxia Huizu Zizhiqu'),
            'CN-QH' => array('Qinghai Sheng'),
            'CN-SC' => array('Sichuan Sheng'),
            'CN-SD' => array('Shandong Sheng'),
            'CN-SH' => array('Shanghai Shi'),
            'CN-SN' => array('Shaanxi Sheng'),
            'CN-SX' => array('Shanxi Sheng'),
            'CN-TJ' => array('Tianjin Shi'),
            'CN-TW' => array('Taiwan Sheng'),
            'CN-XJ' => array('Xinjiang Uygur Zizhiqu'),
            'CN-XZ' => array('Xizang Zizhiqu'),
            'CN-YN' => array('Yunnan Sheng'),
            'CN-ZJ' => array('Zhejiang Sheng'),
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
        'TH' => array(
            'Amnat Charoen' => array('Amnat Charoen'),
            'Ang Thong' => array('Ang Thong'),
            'Bangkok' => array('Bangkok'),
            'Bueng Kan' => array('Bueng Kan'),
            'Buri Ram' => array('Buri Ram'),
            'Chachoengsao' => array('Chachoengsao'),
            'Chai Nat' => array('Chai Nat'),
            'Chaiyaphum' => array('Chaiyaphum'),
            'Chanthaburi' => array('Chanthaburi'),
            'Chiang Mai' => array('Chiang Mai'),
            'Chiang Rai' => array('Chiang Rai'),
            'Chon Buri' => array('Chon Buri'),
            'Chumphon' => array('Chumphon'),
            'Kalasin' => array('Kalasin'),
            'Kamphaeng Phet' => array('Kamphaeng Phet'),
            'Kanchanaburi' => array('Kanchanaburi'),
            'Khon Kaen' => array('Khon Kaen'),
            'Krabi' => array('Krabi'),
            'Lampang' => array('Lampang'),
            'Lamphun' => array('Lamphun'),
            'Loei' => array('Loei'),
            'Lop Buri' => array('Lop Buri'),
            'Mae Hong Son' => array('Mae Hong Son'),
            'Maha Sarakham' => array('Maha Sarakham'),
            'Mukdahan' => array('Mukdahan'),
            'Nakhon Nayok' => array('Nakhon Nayok'),
            'Nakhon Pathom' => array('Nakhon Pathom'),
            'Nakhon Phanom' => array('Nakhon Phanom'),
            'Nakhon Ratchasima' => array('Nakhon Ratchasima'),
            'Nakhon Sawan' => array('Nakhon Sawan'),
            'Nakhon Si Thammarat' => array('Nakhon Si Thammarat'),
            'Nan' => array('Nan'),
            'Narathiwat' => array('Narathiwat'),
            'Nong Bua Lamphu' => array('Nong Bua Lamphu'),
            'Nong Khai' => array('Nong Khai'),
            'Nonthaburi' => array('Nonthaburi'),
            'Pathum Thani' => array('Pathum Thani'),
            'Pattani' => array('Pattani'),
            'Phang Nga' => array('Phang Nga'),
            'Phatthalung' => array('Phatthalung'),
            'Phatthaya' => array('Phatthaya'),
            'Phayao' => array('Phayao'),
            'Phetchabun' => array('Phetchabun'),
            'Phetchaburi' => array('Phetchaburi'),
            'Phichit' => array('Phichit'),
            'Phitsanulok' => array('Phitsanulok'),
            'Phra Nakhon Si Ayutthaya' => array('Phra Nakhon Si Ayutthaya'),
            'Phrae' => array('Phrae'),
            'Phuket' => array('Phuket'),
            'Prachin Buri' => array('Prachin Buri'),
            'Prachuap Khiri Khan' => array('Prachuap Khiri Khan'),
            'Ranong' => array('Ranong'),
            'Ratchaburi' => array('Ratchaburi'),
            'Rayong' => array('Rayong'),
            'Roi Et' => array('Roi Et'),
            'Sa Kaeo' => array('Sa Kaeo'),
            'Sakon Nakhon' => array('Sakon Nakhon'),
            'Samut Prakan' => array('Samut Prakan'),
            'Samut Sakhon' => array('Samut Sakhon'),
            'Samut Songkhram' => array('Samut Songkhram'),
            'Saraburi' => array('Saraburi'),
            'Satun' => array('Satun'),
            'Si Sa Ket' => array('Si Sa Ket'),
            'Sing Buri' => array('Sing Buri'),
            'Songkhla' => array('Songkhla'),
            'Sukhothai' => array('Sukhothai'),
            'Suphan Buri' => array('Suphan Buri'),
            'Surat Thani' => array('Surat Thani'),
            'Surin' => array('Surin'),
            'Tak' => array('Tak'),
            'Trang' => array('Trang'),
            'Trat' => array('Trat'),
            'Ubon Ratchathani' => array('Ubon Ratchathani'),
            'Udon Thani' => array('Udon Thani'),
            'Uthai Thani' => array('Uthai Thani'),
            'Uttaradit' => array('Uttaradit'),
            'Yala' => array('Yala'),
            'Yasothon' => array('Yasothon'),
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
