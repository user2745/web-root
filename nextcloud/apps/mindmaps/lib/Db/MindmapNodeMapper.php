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
use OCP\AppFramework\Db\{Entity, Mapper};
use OCP\IDBConnection;

class MindmapNodeMapper extends Mapper {

	/**
	 * MindmapNodeMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, Application::MINDMAPS_NODES_TABLE);
	}

	/**
	 * Return a mindmap node object by given id.
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
	 * Return all mindmap nodes for a given mindmap.
	 *
	 * @param int $mindmapId
	 * @param null|int $limit
	 * @param null|int $offset
	 *
	 * @return \OCP\AppFramework\Db\Entity[]
	 */
	public function findAll(int $mindmapId, int $limit = null, int $offset = null): array {
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE mindmap_id = ?';
		return $this->findEntities($sql, [$mindmapId], $limit, $offset);
	}

	/**
	 * Get the child nodes for a given mindmap node.
	 *
	 * @param int $id
	 *
	 * @return \OCP\AppFramework\Db\Entity[]
	 */
	private function getChildNodes(int $id): array {
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE parent_id = ?';
		return $this->findEntities($sql, [$id]);
	}

	/**
	 * Delete a mindmap node and all of its child nodes.
	 *
	 * @param Entity $entity
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 */
	public function delete(Entity $entity): Entity {
		$children = $this->getChildNodes($entity->getId());
		foreach ($children as $child) {
			$this->delete($child);
		}
		return parent::delete($entity);
	}

	/**
	 * Delete all child nodes for a given mindmap.
	 *
	 * @param int $mindmapId
	 */
	public function deleteByMindmapId(int $mindmapId) {
		$mindmapNodes = $this->findAll($mindmapId);
		foreach ($mindmapNodes as $node) {
			$this->delete($node);
		}
	}
}
