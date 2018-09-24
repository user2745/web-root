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

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

		// mindmaps
		['name' => 'mindmap#index', 'url' => '/mindmaps', 'verb' => 'GET'],
		['name' => 'mindmap#read', 'url' => '/mindmaps/{id}', 'verb' => 'GET'],
		['name' => 'mindmap#create', 'url' => '/mindmaps', 'verb' => 'POST'],
		['name' => 'mindmap#update', 'url' => '/mindmaps/{id}', 'verb' => 'PUT'],
		['name' => 'mindmap#delete', 'url' => '/mindmaps/{id}', 'verb' => 'DELETE'],

		// mindmap nodes
		['name' => 'mindmapNode#index', 'url' => '/nodes/{mindmapId}', 'verb' => 'GET'],
		['name' => 'mindmapNode#create', 'url' => '/nodes', 'verb' => 'POST'],
		['name' => 'mindmapNode#update', 'url' => '/nodes/{id}', 'verb' => 'PUT'],
		['name' => 'mindmapNode#delete', 'url' => '/nodes/{id}', 'verb' => 'DELETE'],

		// mindmap node locks
		['name' => 'mindmapNode#lock', 'url' => '/nodes/{id}/locks', 'verb' => 'POST'],
		['name' => 'mindmapNode#unlock', 'url' => '/nodes/{id}/locks', 'verb' => 'DELETE'],

		// mindmap acls
		['name' => 'acl#index', 'url' => '/acl/{mindmapId}', 'verb' => 'GET'],
		['name' => 'acl#create', 'url' => '/acl', 'verb' => 'POST'],
		['name' => 'acl#delete', 'url' => '/acl/{id}', 'verb' => 'DELETE']
	]
];
