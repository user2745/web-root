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

namespace OCA\Mindmaps\Controller;

use OCA\Mindmaps\Exception\{BadRequestException, NotFoundException};
use OCA\Mindmaps\Service\MindmapService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class MindmapController extends Controller {

	/** @var MindmapService */
	private $mindmapService;
	/** @var string */
	private $userId;

	/**
	 * MindmapController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param MindmapService $mindmapService
	 * @param string $userId
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		MindmapService $mindmapService,
		string $userId
	) {
		parent::__construct($appName, $request);
		$this->mindmapService = $mindmapService;
		$this->userId = $userId;
	}

	/**
	 * Return all mindmaps as json.
	 *
	 * @NoAdminRequired
	 *
	 * @param null|int $limit
	 * @param null|int $offset
	 *
	 * @return DataResponse
	 */
	public function index(int $limit = null, int $offset = null): DataResponse {
		return new DataResponse(
			$this->mindmapService->findAll($this->userId, $limit, $offset)
		);
	}

	/**
	 * Return a single mindmap by its id.
	 *
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return DataResponse
	 *
	 * @throws \Exception
	 */
	public function read(int $id): DataResponse {
		try {
			return new DataResponse($this->mindmapService->findByUser($id, $this->userId));
		} catch (NotFoundException $ex) {
			return new DataResponse(array('msg' => $ex->getMessage()), $ex->getCode());
		}
	}

	/**
	 * Create a mindmap with the given parameters.
	 *
	 * @NoAdminRequired
	 *
	 * @param string $title
	 * @param string $description
	 *
	 * @return DataResponse
	 */
	public function create(string $title, string $description): DataResponse {
		try {
			return new DataResponse(
				$this->mindmapService->create($title, $description, $this->userId)
			);
		} catch (BadRequestException $ex) {
			return new DataResponse(array('msg' => $ex->getMessage()), $ex->getCode());
		}
	}

	/**
	 * Update a given mindmap with the given parameters.
	 *
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $title
	 * @param string $description
	 *
	 * @return DataResponse
	 *
	 * @throws \Exception
	 */
	public function update(
		int $id,
		string $title,
		string $description
	): DataResponse {
		try {
			return new DataResponse(
				$this->mindmapService->update($id, $title, $description, $this->userId)
			);
		} catch (BadRequestException $ex) {
			return new DataResponse(array('msg' => $ex->getMessage()), $ex->getCode());
		} catch (NotFoundException $ex) {
			return new DataResponse(array('msg' => $ex->getMessage()), $ex->getCode());
		}
	}

	/**
	 * Delete a given mindmap.
	 *
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return DataResponse
	 *
	 * @throws \Exception
	 */
	public function delete(int $id): DataResponse {
		try {
			return new DataResponse($this->mindmapService->delete($id, $this->userId));
		} catch (NotFoundException $ex) {
			return new DataResponse(array('msg' => $ex->getMessage()), $ex->getCode());
		}
	}
}
