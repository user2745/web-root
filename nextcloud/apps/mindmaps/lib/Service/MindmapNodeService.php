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

namespace OCA\Mindmaps\Service;

use Exception;
use OCA\Mindmaps\Db\{
	MindmapMapper, MindmapNode, MindmapNodeMapper
};
use OCA\Mindmaps\Exception\{BadRequestException, NotFoundException};
use OCP\AppFramework\Db\Entity;

class MindmapNodeService extends Service {

	/** @var MindmapMapper */
	private $mindmapMapper;
	/** @var MindmapNodeMapper */
	private $mindmapNodeMapper;

	/**
	 * MindmapNodeService constructor.
	 *
	 * @param MindmapMapper $mindmapMapper
	 * @param MindmapNodeMapper $mindmapNodeMapper
	 */
	public function __construct(
		MindmapMapper $mindmapMapper,
		MindmapNodeMapper $mindmapNodeMapper
	) {
		parent::__construct($mindmapNodeMapper);

		$this->mindmapMapper = $mindmapMapper;
		$this->mindmapNodeMapper = $mindmapNodeMapper;
	}

	/**
	 * Return all mindmap nodes from mapper class by user id and mindmap id.
	 *
	 * @param int $mindmapId
	 * @param string $userId
	 * @param null|int $limit
	 * @param null|int $offset
	 *
	 * @return \OCP\AppFramework\Db\Entity[]
	 *
	 * @throws NotFoundException if user has no access to the given mindmap
	 */
	public function findAll(int $mindmapId, string $userId, int $limit = null, int $offset = null): array {
		if (!$this->mindmapMapper->hasUserAccess($mindmapId, $userId)) {
			throw new NotFoundException();
		}
		return $this->mindmapNodeMapper->findAll($mindmapId, $limit, $offset);
	}

	/**
	 * Create a new mindmap node object and insert it via mapper class.
	 *
	 * @param int $mindmapId
	 * @param string $label
	 * @param int $x
	 * @param int $y
	 * @param string $userId
	 * @param null|int $parentId
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws BadRequestException if parameters are invalid
	 */
	public function create(int $mindmapId, string $label, int $x, int $y, string $userId, int $parentId = null): Entity {
		if ($label === null || $label === '' || \strlen($label) > 255) {
			throw new BadRequestException();
		}

		$mindmapNode = new MindmapNode();
		$mindmapNode->setMindmapId($mindmapId);
		$mindmapNode->setParentId($parentId);
		$mindmapNode->setLabel($label);
		$mindmapNode->setX($x);
		$mindmapNode->setY($y);
		$mindmapNode->setUserId($userId);

		return $this->mindmapNodeMapper->insert($mindmapNode);
	}

	/**
	 * Find and update a given mindmap node object.
	 *
	 * @param int $id
	 * @param string $label
	 * @param int $x
	 * @param int $y
	 * @param string $userId
	 * @param null|int $parentId
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws BadRequestException if parameters are invalid
	 * @throws NotFoundException if user is not allowed to update it
	 * @throws Exception
	 */
	public function update(int $id, string $label, int $x, int $y, string $userId, int $parentId = null): Entity {
		if ($label === null || $label === '' || \strlen($label) > 255) {
			throw new BadRequestException();
		}

		try {
			$mindmapNode = $this->find($id);
			if (!$this->mindmapMapper->hasUserAccess($mindmapNode->getMindmapId(), $userId)) {
				throw new NotFoundException();
			}
			$mindmapNode->setParentId($parentId);
			$mindmapNode->setLabel($label);
			$mindmapNode->setX($x);
			$mindmapNode->setY($y);

			return $this->mindmapNodeMapper->update($mindmapNode);
		} catch (Exception $e) {
			$this->handleException($e);
		}
		return null;
	}

	/**
	 * Find and lock a given mindmap node object.
	 *
	 * @param int $id
	 * @param string $userId
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws BadRequestException if the node is already locked by another user
	 * @throws Exception
	 */
	public function lock(int $id, string $userId): Entity {
		try {
			$mindmapNode = $this->find($id);
			if ($mindmapNode->getLockedBy() !== null) {
				throw new BadRequestException();
			}
			$mindmapNode->setLockedBy($userId);

			return $this->mindmapNodeMapper->update($mindmapNode);
		} catch (Exception $e) {
			$this->handleException($e);
		}
		return null;
	}

	/**
	 * Find and unlock a given mindmap node object.
	 *
	 * @param int $id
	 * @param string $userId
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws NotFoundException if the user is not allowed to unlock it
	 * @throws Exception
	 */
	public function unlock(int $id, string $userId): Entity {
		try {
			$mindmapNode = $this->find($id);
			if ($mindmapNode->getUserId() !== $userId) {
				throw new NotFoundException();
			}
			$mindmapNode->setLockedBy(null);

			return $this->mindmapNodeMapper->update($mindmapNode);
		} catch (Exception $e) {
			$this->handleException($e);
		}
		return null;
	}

	/**
	 * Find and delete the entity by given id and user id.
	 *
	 * @param int $id
	 * @param string $userId
	 *
	 * @return null|\OCP\AppFramework\Db\Entity
	 *
	 * @throws NotFoundException if the mindmap does not exist or user is not allowed to delete it
	 * @throws Exception
	 */
	public function delete(int $id, string $userId): Entity {
		try {
			$entity = $this->find($id);
			if (!$this->mindmapMapper->hasUserAccess($entity->getMindmapId(), $userId)) {
				throw new NotFoundException();
			}
			return $this->mapper->delete($entity);
		} catch (Exception $e) {
			$this->handleException($e);
		}
		return null;
	}
}
