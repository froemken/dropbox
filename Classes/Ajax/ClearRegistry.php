<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ClearRegistry
 *
 * @author Stefan Froemken <froemken@gmail.com>
 */
class ClearRegistry {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Registry
	 */
	protected $registry;

	public function clearSysRegistry() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->registry = $this->objectManager->get('TYPO3\\CMS\\Core\\Registry');
		$this->registry->remove('fal_dropbox', 'settings');
	}
}

$clearRegistry = new ClearRegistry();
$clearRegistry->clearSysRegistry();
?>