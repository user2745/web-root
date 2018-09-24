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
	Acl, AclMapper, MindmapMapper
};
use OCA\Mindmaps\Exception\{BadRequestException, NotFoundException};
use OCP\AppFramework\Db\Entity;

class AclService extends Service {

	/** @var MindmapMapper */
	private $mindmapMapper;
	/** @var AclMapper */
	private $aclMapper;

	/**
	 * AclService constructor.
	 *
	 * @param AclMapper $aclMapper
	 * @param MindmapMapper $mindmapMapper
	 */
	public function __construct(
		MindmapMapper $mindmapMapper,
		AclMapper $aclMapper
	) {
		parent::__construct($aclMapper);

		$this->mindmapMapper = $mindmapMapper;
		$this->aclMapper = $aclMapper;
	}

	/**
	 * Return all acl entities for a specific mindmap grouped by limit and offset.
	 *
	 * @param int $mindmapId
	 * @param null|int $limit
	 * @param null|int $offset
	 *
	 * @return \OCP\AppFramework\Db\Entity[]
	 */
	public function findAll(int $mindmapId, int $limit = null, int $offset = null): array {
		return $this->aclMapper->findAll($mindmapId, $limit, $offset);
	}

	/**
	 * Create a new acl object and insert it via mapper class.
	 *
	 * @param int $mindmapId
	 * @param int $type
	 * @param string $participant
	 *
	 * @return \OCP\AppFramework\Db\Entity
	 *
	 * @throws BadRequestException if parameters are invalid
	 */
	public function create(int $mindmapId, int $type, string $participant): Entity {
		if ($participant === null || $participant === '' || \strlen($participant) > 255) {
			throw new BadRequestException();
		}

		$acl = new Acl();
		$acl->setMindmapId($mindmapId);
		$acl->setType($type);
		$acl->setParticipant($participant);

		return $this->aclMapper->insert($acl);
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
