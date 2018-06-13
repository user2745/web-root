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

use OCP\AppFramework\Db\Entity;

abstract class Model extends Entity {

	/**
	 * FactoryMuffin checks for the existence of setters with method_exists($obj, $attr) but that returns false.
	 * By overwriting the __set() magic method we can trigger the changed flag on $obj->attr assignment.
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value) {
		$this->setter($name, [$value]);
	}
}
