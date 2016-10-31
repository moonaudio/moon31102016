<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Locale
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'en-US', 'label' => Mage::helper('googlebasefeedgenerator')->__('en-US')),
            array('value' => 'cs-CZ', 'label' => Mage::helper('googlebasefeedgenerator')->__('cs-CZ')),
            array('value' => 'de-DE', 'label' => Mage::helper('googlebasefeedgenerator')->__('de-DE')),
            array('value' => 'de-CH', 'label' => Mage::helper('googlebasefeedgenerator')->__('de-CH')),
            array('value' => 'da-DK', 'label' => Mage::helper('googlebasefeedgenerator')->__('da-DK')),
            array('value' => 'en-AU', 'label' => Mage::helper('googlebasefeedgenerator')->__('en-AU')),
            array('value' => 'en-CA', 'label' => Mage::helper('googlebasefeedgenerator')->__('en-CA')),
            array('value' => 'en-GB', 'label' => Mage::helper('googlebasefeedgenerator')->__('en-GB')),
            array('value' => 'es-ES', 'label' => Mage::helper('googlebasefeedgenerator')->__('es-ES')),
            array('value' => 'fr-FR', 'label' => Mage::helper('googlebasefeedgenerator')->__('fr-FR')),
            array('value' => 'it-IT', 'label' => Mage::helper('googlebasefeedgenerator')->__('it-IT')),
            array('value' => 'ja-JP', 'label' => Mage::helper('googlebasefeedgenerator')->__('ja-JP')),
            array('value' => 'nl-NL', 'label' => Mage::helper('googlebasefeedgenerator')->__('nl-NL')),
            array('value' => 'pl-PL', 'label' => Mage::helper('googlebasefeedgenerator')->__('pl-PL')),
            array('value' => 'pt-BR', 'label' => Mage::helper('googlebasefeedgenerator')->__('pt-BR')),
            array('value' => 'ru-RU', 'label' => Mage::helper('googlebasefeedgenerator')->__('ru-RU')),
            array('value' => 'sv-SE', 'label' => Mage::helper('googlebasefeedgenerator')->__('sv-SE')),
            array('value' => 'no-NO', 'label' => Mage::helper('googlebasefeedgenerator')->__('no-NO')),
            array('value' => 'tr-TR', 'label' => Mage::helper('googlebasefeedgenerator')->__('tr-TR')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'en-US' => Mage::helper('googlebasefeedgenerator')->__('en-US'),
            'cs-CZ' => Mage::helper('googlebasefeedgenerator')->__('cs-CZ'),
            'de-DE' => Mage::helper('googlebasefeedgenerator')->__('de-DE'),
            'de-CH' => Mage::helper('googlebasefeedgenerator')->__('de-CH'),
            'da-DK' => Mage::helper('googlebasefeedgenerator')->__('da-DK'),
            'en-AU' => Mage::helper('googlebasefeedgenerator')->__('en-AU'),
            'en-CA' => Mage::helper('googlebasefeedgenerator')->__('en-CA'),
            'en-GB' => Mage::helper('googlebasefeedgenerator')->__('en-GB'),
            'es-ES' => Mage::helper('googlebasefeedgenerator')->__('es-ES'),
            'fr-FR' => Mage::helper('googlebasefeedgenerator')->__('fr-FR'),
            'it-IT' => Mage::helper('googlebasefeedgenerator')->__('it-IT'),
            'ja-JP' => Mage::helper('googlebasefeedgenerator')->__('ja-JP'),
            'nl-NL' => Mage::helper('googlebasefeedgenerator')->__('nl-NL'),
            'pl-PL' => Mage::helper('googlebasefeedgenerator')->__('pl-PL'),
            'pt-BR' => Mage::helper('googlebasefeedgenerator')->__('pt-BR'),
            'ru-RU' => Mage::helper('googlebasefeedgenerator')->__('ru-RU'),
            'sv-SE' => Mage::helper('googlebasefeedgenerator')->__('sv-SE'),
            'no-NO' => Mage::helper('googlebasefeedgenerator')->__('no-NO'),
            'tr-TR' => Mage::helper('googlebasefeedgenerator')->__('tr-TR'),
        );
    }

}