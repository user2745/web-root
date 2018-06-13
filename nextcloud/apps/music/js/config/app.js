
/**
 * ownCloud - Music app
 *
 * @author Morris Jobke
 * @copyright 2013 Morris Jobke <morris.jobke@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// fix SVGs in IE because the scaling is a real PITA
// https://github.com/owncloud/music/issues/126
if($('html').hasClass('ie')) {
	var replaceSVGs = function() {
		replaceSVG();
		// call them periodically to keep track of possible changes in the artist view
		setTimeout(replaceSVG, 10000);
	};
	replaceSVG();
	setTimeout(replaceSVG, 1000);
	setTimeout(replaceSVGs, 5000);
}

angular.module('Music', ['restangular', 'duScroll', 'gettext', 'ngRoute', 'ang-drag-drop', 'pasvaz.bindonce'])
	.config(['RestangularProvider', '$routeProvider', '$locationProvider',
		function (RestangularProvider, $routeProvider, $locationProvider) {

			// configure RESTAngular path
			RestangularProvider.setBaseUrl('api');

			var overviewControllerConfig = {
				controller:'OverviewController',
				templateUrl:'overview.html'
			};

			var playlistControllerConfig = {
				controller:'PlaylistViewController',
				templateUrl:'playlistview.html'
			};

			var settingsControllerConfig = {
				controller:'SettingsViewController',
				templateUrl:'settingsview.html'
			};

			/**
			 * @see https://stackoverflow.com/questions/38455077/angular-force-an-undesired-exclamation-mark-in-url/41223197#41223197
			 */
			$locationProvider.hashPrefix('');

			$routeProvider
				.when('/',                     overviewControllerConfig)
				.when('/artist/:id',           overviewControllerConfig)
				.when('/album/:id',            overviewControllerConfig)
				.when('/track/:id',            overviewControllerConfig)
				.when('/file/:id',             overviewControllerConfig)
				.when('/playlist/:playlistId', playlistControllerConfig)
				.when('/alltracks',            playlistControllerConfig)
				.when('/settings',             settingsControllerConfig);
		}
	])
	.run(['Token', 'Restangular',
		function(Token, Restangular){
			// add CSRF token
			Restangular.setDefaultHeaders({requesttoken: Token});
		}
	]);
