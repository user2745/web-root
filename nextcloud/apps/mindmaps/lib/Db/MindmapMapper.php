<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mindmaps\Db;

use OCA\Mindmaps\AppInfo\Application;
use OCA\Mindmaps\Util;
use OCP\AppFramework\Db\{
	DoesNotExistException, Entity, Mapper
};
use OCP\{IDBConnection, IGroupManager, IUserManager};

class MindmapMapper extends Mapper {

	/** @var MindmapNodeMapper */
	private $mindmapNodeMapper;
	/** @var AclMapper */
	private $aclMapper;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IUserManager */
	private $userManager;

	/**
	 * MindmapMapper constructor.
	 *
	 * @param IDBConnection $db
	 * @param MindmapNodeMapper $mindmapNodeMapper
	 * @param AclMapper $aclMapper
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 */
	public function __construct(
		IDBConnection $db,
		MindmapNodeMapper $mindmapNodeMapper,
		AclMapper $aclMapper,
		IGroupManager $groupManager,
		IUserManager $userManager
	) {
		parent::__construct($db, Application::MINDMAPS_TABLE);
		$this->mindmapNodeMapper = $mindmapNodeMapper;
		$this->aclMapper = $aclMapper;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
	}

	/**
	 * Converts an array to an SQL string list sth. like 'test', 'test' which can be wrapped by IN ().
	 *
	 * @param array $array
	 * @return string
	 */
	private function arrayToSqlList(array $array): string {
		$result = '';
		foreach ($array as $key => $value) {
			$result .= "'" . $value . "'" . (($key !== \count($array) - 1) ? ', ' : '');
		}
		return $result;
	}

	/**
	 * Return a mindmap object by given id.
	 *
	 * @param int $id
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 */
	public function find(int $id): Entity {
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE id = ?';
		return $this->findEntity($sql, [$id]);
	}

	/**
	 * Return a mindmap object by given id and userId (with access check).
	 *
	 * @param int $id
	 * @param string $userId
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 */
	public function findByUser(int $id, string $userId): Entity {
		if (!$this->hasUserAccess($id, $userId)) {
			throw new DoesNotExistException('Mindmap not found.');
		}
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE id = ?';
		return $this->findEntity($sql, [$id]);
	}

	/**
	 * Return all mindmaps for a specific user also includes shared mindmaps.
	 *
	 * @param string $userId
	 * @param null|int $limit
	 * @param null|int $offset
	 *
	 * @return \OCP\AppFramework\Db\Entity[]
	 */
	public function findAll(string $userId, int $limit = null, int $offset = null): array {
		// Get circle ids for the given user
		$circleIds = [];
		if (Util::isCirclesAppEnabled()) {
			/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
			/** @var \OCA\Circles\Model\Circle[] $userCircles */
			$userCircles = \OCA\Circles\Api\v1\Circles::listCircles(\OCA\Circles\Model\Circle::CIRCLES_ALL);
			foreach ($userCircles as $circle) {
				$circleIds[] = $circle->getUniqueId();
			}
		}
		// Get group ids for the given user
		$user = $this->userManager->get($userId);
		$groupIds = [];
		if ($user !== null) {
			$groupIds = $this->groupManager->getUserGroupIds($user);
		}

		// The parameter bindings need to be variable depending on the circles / groups
		$queryParameters = [
			$userId,
			$userId,
			\OCP\Share::SHARE_TYPE_USER
		];

		// Build the SQL string
		$sql = 'SELECT ' .
			'  DISTINCT(*PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.mindmap_id) IS NOT NULL AS shared, ' .
			'  ' . $this->getTableName() . '.* ' .
			'FROM ' . $this->getTableName() . ' ' .
			'  LEFT JOIN *PREFIX*' . Application::MINDMAPS_ACL_TABLE . ' ON ' . $this->getTableName() . '.id = *PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.mindmap_id ' .
			'WHERE ' . $this->getTableName() . '.user_id = ? OR ' .
			'      *PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.participant = ? AND *PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.type = ? ';

		// Do we need to query by group?
		if (\count($groupIds) > 0) {
			$sql .= 'OR *PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.participant IN (' . $this->arrayToSqlList($groupIds) . ') AND *PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.type = ? ';
			$queryParameters[] = \OCP\Share::SHARE_TYPE_GROUP;
		}

		// Do we need to query by circle?
		if (\count($circleIds) > 0) {
			$sql .= 'OR *PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.participant IN (' . $this->arrayToSqlList($circleIds) . ') AND *PREFIX*' . Application::MINDMAPS_ACL_TABLE . '.type = ? ';
			$queryParameters[] = \OCP\Share::SHARE_TYPE_CIRCLE;
		}

		$sql .= 'ORDER BY ' . $this->getTableName() . '.id';

		return $this->findEntities(
			$sql,
			$queryParameters,
			$limit,
			$offset
		);
	}

	/**
	 * Check if a given user has access to the passed mindmap.
	 *
	 * @param int $mindmapId
	 * @param string $userId
	 * @return bool
	 */
	public function hasUserAccess(int $mindmapId, string $userId): bool {
		$userMindmaps = $this->findAll($userId);
		foreach ($userMindmaps as $mindmap) {
			if ($mindmap->getId() === $mindmapId) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Deletes an entity and its children from the tables.
	 *
	 * @param \OCP\AppFramework\Db\Entity $entity the entity that should be deleted
	 *
	 * @return \OCP\AppFramework\Db\Entity the deleted entity
	 */
	public function delete(Entity $entity): Entity {
		$this->mindmapNodeMapper->deleteByMindmapId($entity->getId());
		$this->aclMapper->deleteByMindmapId($entity->getId());
		return parent::delete($entity);
	}
}
