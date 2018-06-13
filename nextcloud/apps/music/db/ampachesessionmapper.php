<?php

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @copyright Morris Jobke 2013, 2014
 */

namespace OCA\Music\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

class AmpacheSessionMapper extends Mapper {

	public function __construct(IDBConnection $db){
		parent::__construct($db, 'music_ampache_sessions', '\OCA\Music\Db\AmpacheSession');
	}

	/**
	 * @param string $token
	 */
	public function findByToken($token){
		$sql = 'SELECT `user_id` '.
			'FROM `*PREFIX*music_ampache_sessions` '.
			'WHERE `token` = ? AND `expiry` > ?';
		$params = array($token, time());

		$result = $this->execute($sql, $params);

		// false if no row could be fetched
		return $result->fetch();
	}

	/**
	 * @param string $token
	 * @param integer $expiry
	 */
	public function extend($token, $expiry){
		$sql = 'UPDATE `*PREFIX*music_ampache_sessions` '.
			'SET `expiry` = ? '.
			'WHERE `token` = ?';

		$params = array($expiry, $token);
		$this->execute($sql, $params);
	}

	public function cleanUp(){
		$sql = 'DELETE FROM `*PREFIX*music_ampache_sessions` '.
			'WHERE `expiry` < ?';
		$params = array(time());
		$this->execute($sql, $params);
	}
}
