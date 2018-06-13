/*
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

app.factory('CardService', function (ApiService, $http, $q) {
	var CardService = function ($http, ep, $q) {
		ApiService.call(this, $http, ep, $q);
	};
	CardService.prototype = angular.copy(ApiService.prototype);

	CardService.prototype.reorder = function (card, order) {
		var deferred = $q.defer();
		var self = this;
		$http.put(this.baseUrl + '/' + card.id + '/reorder', {
			cardId: card.id,
			order: order,
			stackId: card.stackId
		}).then(function (response) {
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;
	};

	CardService.prototype.rename = function (card) {
		var deferred = $q.defer();
		var self = this;
		$http.put(this.baseUrl + '/' + card.id + '/rename', {
			cardId: card.id,
			title: card.title
		}).then(function (response) {
			self.data[card.id].title = card.title;
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while renaming ' + self.endpoint);
		});
		return deferred.promise;
	};

	CardService.prototype.assignLabel = function (card, label) {
		var url = this.baseUrl + '/' + card + '/label/' + label;
		var deferred = $q.defer();
		var self = this;
		$http.post(url).then(function (response) {
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;
	};
	CardService.prototype.removeLabel = function (card, label) {
		var url = this.baseUrl + '/' + card + '/label/' + label;
		var deferred = $q.defer();
		var self = this;
		$http.delete(url).then(function (response) {
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;
	};

	CardService.prototype.archive = function (card) {
		var deferred = $q.defer();
		var self = this;
		$http.put(this.baseUrl + '/' + card.id + '/archive', {}).then(function (response) {
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;

	};

	CardService.prototype.unarchive = function (card) {
		var deferred = $q.defer();
		var self = this;
		$http.put(this.baseUrl + '/' + card.id + '/unarchive', {}).then(function (response) {
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;
	};

	CardService.prototype.assignUser = function (card, user) {
		var deferred = $q.defer();
		var self = this;
		if (self.getCurrent().assignedUsers === null) {
			self.getCurrent().assignedUsers = [];
		}
		$http.post(this.baseUrl + '/' + card.id + '/assign', {'userId': user}).then(function (response) {
			self.getCurrent().assignedUsers.push(response.data);
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;

	};

	CardService.prototype.unassignUser = function (card, user) {
		var deferred = $q.defer();
		var self = this;
		$http.delete(this.baseUrl + '/' + card.id + '/assign/' + user, {}).then(function (response) {
			self.getCurrent().assignedUsers = self.getCurrent().assignedUsers.filter(function (obj) {
				return obj.participant.uid !== user;
			});
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;
	};

	var service = new CardService($http, 'cards', $q);
	return service;
});