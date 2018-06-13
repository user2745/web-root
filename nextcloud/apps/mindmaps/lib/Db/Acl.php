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

use JsonSerializable;
use OCA\Mindmaps\Util;
use OCP\{IGroupManager, IUserManager, Share};

/**
 * @method string getParticipant()
 * @method void setParticipant(string $participant)
 * @method int getType()
 * @method void setType(int $type)
 * @method int getMindmapId()
 * @method void setMindmapId(int $mindmapId)
 */
class Acl extends Model implements JsonSerializable {

	/** @var IUserManager */
	private $userManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var string */
	protected $participant;
	/** @var integer */
	protected $type;
	/** @var integer */
	protected $mindmapId;

	/**
	 * Acl constructor.
	 */
	public function __construct() {
		$this->userManager = \OC::$server->query(IUserManager::class);
		$this->groupManager = \OC::$server->query(IGroupManager::class);

		$this->addType('type', 'integer');
		$this->addType('mindmapId', 'integer');
	}

	/**
	 * Returns the full name of the user / group / circle which is the participant.
	 *
	 * @return string
	 */
	public function participantDisplayName(): string {
		if ($this->getType() === Share::SHARE_TYPE_USER) {
			$user = $this->userManager->get($this->getParticipant());
			return ($user !== null) ? $user->getDisplayName() : $this->getParticipant();
		}

		if ($this->getType() === Share::SHARE_TYPE_GROUP) {
			$group = $this->groupManager->get($this->getParticipant());
			return ($group !== null) ? $group->getDisplayName() : $this->getParticipant();
		}

		// Check if the circles app is installed and enabled for the current user
		if ($this->getType() === Share::SHARE_TYPE_CIRCLE && Util::isCirclesAppEnabled()) {
			/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
			$circle = \OCA\Circles\Api\v1\Circles::detailsCircle($this->getParticipant());
			return ($circle !== null) ? $circle->getName() : $this->getParticipant();
		}

		return $this->getParticipant();
	}

	/**
	 * Return object as json string.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'participant' => $this->participant,
			'participantDisplayName' => $this->participantDisplayName(),
			'type' => $this->type,
			'mindmapId' => $this->mindmapId
		];
	}
}
