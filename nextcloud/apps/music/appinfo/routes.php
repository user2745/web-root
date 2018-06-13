<?php

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @copyright Morris Jobke 2014
 */

namespace OCA\Music;

use \OCA\Music\App\Music;

$app = new Music();

$app->registerRoutes($this, array('routes' => array(
	// page
	array('name' => 'page#index', 'url' => '/', 'verb' => 'GET'),

	// log
	array('name' => 'log#log', 'url' => '/api/log', 'verb' => 'POST'),

	// api
	array('name' => 'api#collection', 'url' => '/api/collection', 'verb' => 'GET'),
	array('name' => 'api#trackByFileId', 'url' => '/api/file/{fileId}', 'verb' => 'GET'),
	array('name' => 'api#fileWebDavUrl', 'url' => '/api/file/{fileId}/webdav', 'verb' => 'GET'),
	array('name' => 'api#download', 'url' => '/api/file/{fileId}/download', 'verb' => 'GET'),
	array('name' => 'api#fileInfo', 'url' => '/api/file/{fileId}/info', 'verb' => 'GET'),
	array('name' => 'api#getScanState', 'url' => '/api/scanstate', 'verb' => 'GET'),
	array('name' => 'api#scan', 'url' => '/api/scan', 'verb' => 'POST'),
	array('name' => 'api#cachedCover', 'url' => '/api/cover/{hash}', 'verb' => 'GET'),

	// Shiva api https://github.com/tooxie/shiva-server#resources
	array('name' => 'api#artists', 'url' => '/api/artists', 'verb' => 'GET'),
	array('name' => 'api#artist', 'url' => '/api/artist/{artistIdOrSlug}', 'verb' => 'GET'),
	// array('name' => 'api#artistShows', 'url' => '/api/artist/{artistIdOrSlug}/shows', 'verb' => 'GET'),
	array('name' => 'api#albums', 'url' => '/api/albums', 'verb' => 'GET'),
	array('name' => 'api#album', 'url' => '/api/album/{albumIdOrSlug}', 'verb' => 'GET'),
	array('name' => 'api#cover', 'url' => '/api/album/{albumIdOrSlug}/cover', 'verb' => 'GET'),
	array('name' => 'api#tracks', 'url' => '/api/tracks', 'verb' => 'GET'),
	array('name' => 'api#track', 'url' => '/api/track/{trackIdOrSlug}', 'verb' => 'GET'),
	// array('name' => 'api#trackLyrics', 'url' => '/api/track/{trackIdOrSlug}/lyrics', 'verb' => 'GET'),

	// playlist API
	array('name' => 'playlistApi#getAll',		'url' => '/api/playlists',				'verb' => 'GET'),
	array('name' => 'playlistApi#create',		'url' => '/api/playlists',				'verb' => 'POST'),
	array('name' => 'playlistApi#get',			'url' => '/api/playlists/{id}',			'verb' => 'GET'),
	array('name' => 'playlistApi#delete',		'url' => '/api/playlists/{id}',			'verb' => 'DELETE'),
	array('name' => 'playlistApi#update',		'url' => '/api/playlists/{id}',			'verb' => 'PUT'),
	array('name' => 'playlistApi#addTracks',	'url' => '/api/playlists/{id}/add',		'verb' => 'POST'),
	array('name' => 'playlistApi#removeTracks',	'url' => '/api/playlists/{id}/remove',	'verb' => 'POST'),
	array('name' => 'playlistApi#reorder',		'url' => '/api/playlists/{id}/reorder',	'verb' => 'POST'),

	// settings
	array('name' => 'setting#userPath', 'url' => '/api/settings/user/path', 'verb' => 'POST'),
	array('name' => 'setting#getAll', 'url' => '/api/settings', 'verb' => 'GET'),
	array('name' => 'setting#addUserKey', 'url' => '/api/settings/userkey/add', 'verb' => 'POST'),
	array('name' => 'setting#generateUserKey', 'url' => '/api/settings/userkey/generate', 'verb' => 'POST'),
	array('name' => 'setting#removeUserKey', 'url' => '/api/settings/userkey/remove', 'verb' => 'POST'),

	// ampache
	array('name' => 'ampache#ampache', 'url' => '/ampache/server/xml.server.php', 'verb' => 'GET'),
	// Ampache API http://ampache.org/wiki/dev:xmlapi - POST version. Dirty fix for JustPlayer
	array('name' => 'ampache#ampache2', 'url' => '/ampache/server/xml.server.php', 'verb' => 'POST')


)));
