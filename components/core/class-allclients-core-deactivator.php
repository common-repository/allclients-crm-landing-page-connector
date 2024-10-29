<?php

/**
 * Fired during plugin deactivation.
 *
 * @package    AllClients
 * @subpackage AllClients/core
 */
class AllClients_Core_Deactivator {

	/**
	 * Fires on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

}
