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
	Mindmap, MindmapMapper, MindmapNode, MindmapNodeMapper
};
use OCA\Mindmaps\Exception\{BadRequestException, NotFoundException};
use OCP\AppFramework\Db\Entity;

class MindmapService extends Service {

	/** @var MindmapMapper */
	private $mindmapMapper;
	/** @var MindmapNodeMapper */
	private $mindmapNodeMapper;

	/**
	 * MindmapService constructor.
	 *
	 * @param MindmapMapper $mindmapMapper
	 * @param MindmapNodeMapper $mindmapNodeMapper
	 */
	public function __construct(
		MindmapMapper $mindmapMapper,
		MindmapNodeMapper $mindmapNodeMapper
	) {
		parent::__construct($mindmapMapper);

		$this->mindmapMapper = $mindmapMapper;
		$this->mindmapNodeMapper = $mindmapNodeMapper;
	}

	/**
	 * Return all mindmaps from mapper class by user id.
	 *
	 * @param string $userId
	 * @param null|int $limit
	 * @param null|int $offset
	 *
	 * @return \OCP\AppFramework\Db\Entity[]
	 */
	public function findAll(string $userId, int $limit = null, int $offset = null): array {
		return $this->mindmapMapper->findAll($userId, $limit, $offset);
	}

	/**
	 * Find the entity by given id and user id.
	 *
	 * @param int $id
	 * @param string $userId
	 *
	 * @return null|\OCP\AppFramework\Db\Entity
	 *
	 * @throws NotFoundException
	 * @throws Exception
	 */
	public function findByUser($id, $userId): Entity {
		try {
			return $this->mindmapMapper->findByUser($id, $userId);
		} catch (Exception $e) {
			$this->handleException($e);
		}
		return null;
	}

	/**
	 * Create a new mindmap object and insert it via mapper class.
	 *
	 * @param string $title
	 * @param string $description
	 * @param string $userId
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws BadRequestException if parameters are invalid
	 */
	public function create(string $title, string $description, string $userId): Entity {
		if ($title === null || $title === '' || \strlen($title) > 255 || \strlen($description) > 255) {
			throw new BadRequestException();
		}

		// Create the mindmap and persist it.
		$mindmap = new Mindmap();
		$mindmap->setTitle($title);
		$mindmap->setDescription($description);
		$mindmap->setUserId($userId);
		/** @var Mindmap $mindmap */
		$mindmap = $this->mindmapMapper->insert($mindmap);
		// Add the first node to the new mindmap.
		$mindmapNode = new MindmapNode();
		$mindmapNode->setMindmapId($mindmap->getId());
		$mindmapNode->setLabel($mindmap->getTitle());
		$mindmapNode->setUserId($mindmap->getUserId());
		$mindmapNode->setX(0);
		$mindmapNode->setY(0);
		$this->mindmapNodeMapper->insert($mindmapNode);

		return $mindmap;
	}

	/**
	 * Find and update a given mindmap object.
	 *
	 * @param int $id
	 * @param string $title
	 * @param string $description
	 * @param string $userId
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws BadRequestException if parameters are invalid
	 * @throws NotFoundException if user is not allowed to update it
	 * @throws Exception
	 */
	public function update(int $id, string $title, string $description, string $userId): Entity {
		if ($title === null || $title === '' || \strlen($title) > 255 || \strlen($description) > 255) {
			throw new BadRequestException();
		}

		try {
			$mindmap = $this->find($id);
			if (!$this->mindmapMapper->hasUserAccess($id, $userId)) {
				throw new NotFoundException();
			}
			$mindmap->setTitle($title);
			if ($description !== null && $mindmap->getDescription() !== $description) {
				$mindmap->setDescription($description);
			}

			return $this->mindmapMapper->update($mindmap);
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
			if (!$this->mindmapMapper->hasUserAccess($id, $userId)) {
				throw new NotFoundException();
			}
			return $this->mapper->delete($entity);
		} catch (Exception $e) {
			$this->handleException($e);
		}
		return null;
	}
}
