<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Mato Ilic <info@matoilic.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_T3chimp_Provider_Settings implements t3lib_Singleton {
    /**
     * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
     */
    private $configurationManager;

    /**
     * @var Tx_T3chimp_Session_Provider
     */
    private $session;

    /**
     * @var array
     */
    private $settings = array();

    /**
     * @param mixed $settings
     * @return array
     */
    private function cleanSettingKeys($settings) {
        if(!is_array($settings)) {
            return $settings;
        }

        $cleanedSettings = array();
        foreach($settings as $key => $value) {
            if(substr($key, -1) == '.') {
                $key = substr($key, 0, strlen($key) - 1);
            }

            $cleanedSettings[$key] = $this->cleanSettingKeys($value);
        }

        return $cleanedSettings;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        return $this->settings[$key];
    }

    /**
     * @return array
     */
    public function getAll() {
        return $this->settings;
    }

    /**
     * @return bool
     */
    public function getIsCacheDisabled() {
        $config = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        return array_key_exists('no_cache', $config['config.']) && $config['config.']['no_cache'] == '1';
    }

    /**
     * @param Tx_Extbase_Object_ObjectManager $manager
     */
    public function injectObjectManager(Tx_Extbase_Object_ObjectManager $manager) {
        $this->injectSessionProvider($manager->get('Tx_T3chimp_Provider_Session'));
        $this->injectConfigurationManager($manager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface'));
    }

    /**
     * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $manager
     */
    private function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $manager) {
        $this->configurationManager = $manager;

        $this->settings = $manager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
        if($this->settings == NULL) {
            $this->settings = array();
        }

        //read session stored settings for ajax requests
        if(array_key_exists('type', $_GET) && $this->session->settings != null) {
            $this->settings = $this->session->settings;
        } else {
            $this->session->settings = $this->settings;
        }

        $this->settings = array_merge($this->settings, $manager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK));
        $global = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['t3chimp']);
        $global = $this->cleanSettingKeys($global);
        $this->settings = array_merge($this->settings, $global);
    }

    /**
     * @param Tx_T3chimp_Provider_Session $provider
     */
    private function injectSessionProvider(Tx_T3chimp_Provider_Session $provider) {
        $this->session = $provider;
    }
}
