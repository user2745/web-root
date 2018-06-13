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

class AmpacheUserMapper extends Mapper {

	public function __construct(IDBConnection $db){
		// there is no entity for this mapper -> '' as entity class name
		parent::__construct($db, 'music_ampache_users', '');
	}

	/**
	 * @param string $userId
	 */
	public function getPasswordHashes($userId){
		$sql = 'SELECT `hash` FROM `*PREFIX*music_ampache_users` '.
			'WHERE `user_id` = ?';
		$params = array($userId);
		$result = $this->execute($sql, $params);
		$rows = $result->fetchAll();

		$hashes = array();
		foreach ($rows as $value) {
			$hashes[] = $value['hash'];
		}

		return $hashes;
	}

	/**
	 * @param string $userId
	 * @param string $hash
	 * @param string $description
	 */
	public function addUserKey($userId, $hash, $description){
		$sql = 'INSERT INTO `*PREFIX*music_ampache_users` '.
			'(`user_id`, `hash`, `description`) VALUES (?, ?, ?)';
		$params = array($userId, $hash, $description);
		$this->execute($sql, $params);

		$sql = 'SELECT `id` FROM `*PREFIX*music_ampache_users` '.
				'WHERE `user_id` = ? AND `hash` = ?';
		$params = array($userId, $hash);
		$result = $this->execute($sql, $params, 1);
		$row = $result->fetch();

		if($row === false){
			return null;
		}

		return $row['id'];
	}

	/**
	 * @param string $userId
	 * @param integer|string $id
	 */
	public function removeUserKey($userId, $id){
		$sql = 'DELETE FROM `*PREFIX*music_ampache_users` '.
				'WHERE `user_id` = ? AND `id` = ?';
		$params = array($userId, $id);
		$this->execute($sql, $params);
	}

	/**
	 * @param string $userId
	 */
	public function getAll($userId) {
		$sql = 'SELECT `id`, `hash`, `description` FROM `*PREFIX*music_ampache_users` '.
			'WHERE `user_id` = ?';
		$params = array($userId);
		$result = $this->execute($sql, $params);
		$rows = $result->fetchAll();

		return $rows;
	}
}
