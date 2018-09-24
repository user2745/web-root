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

/**
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getAclId()
 * @method void setAclId(int $aclId)
 */
class Mindmap extends Model implements JsonSerializable {

	/** @var string */
	protected $title;
	/** @var string */
	protected $description;
	/** @var string */
	protected $userId;
	/** @var boolean */
	protected $shared;

	/**
	 * Mindmap constructor.
	 */
	public function __construct() {
		$this->addType('shared', 'boolean');
	}

	/**
	 * Return object as json string.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'title' => $this->title,
			'description' => $this->description,
			'userId' => $this->userId,
			'shared' => $this->shared
		];
	}
}
