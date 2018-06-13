
/* global angular */


angular.module('markdown', [])
	.provider('markdown', [function () {
		var opts = {};
		return {
			config: function (newOpts) {
				opts = newOpts;
			},
			$get: function () {
				return new window.showdown.Converter(opts);
			}
		};
	}])
	.filter('markdown', ['markdown', function (markdown) {
		return function (text) {
			return markdown.makeHtml(text || '');
		};
	}]);

var app = angular.module('Deck', [
	'ngRoute',
	'ngSanitize',
	'ui.router',
	'ui.select',
	'as.sortable',
	'mdMarkdownIt',
	'ngAnimate'
]);


/* global app oc_requesttoken markdownitLinkTarget */

app.config(function ($provide, $routeProvider, $interpolateProvider, $httpProvider, $urlRouterProvider, $stateProvider, $compileProvider, markdownItConverterProvider) {
	'use strict';
	$httpProvider.defaults.headers.common.requesttoken = oc_requesttoken;

	$compileProvider.debugInfoEnabled(true);

	markdownItConverterProvider.config({
		breaks: true,
		linkify: true,
		xhtmlOut: true
	});
	markdownItConverterProvider.use(markdownitLinkTarget);

	$urlRouterProvider.otherwise('/');

	$stateProvider
		.state('list', {
			url: '/:filter',
			templateUrl: '/boardlist.mainView.html',
			controller: 'ListController',
			reloadOnSearch: false,
			params: {
				filter: {value: '', dynamic: true}
			}
		})
		.state('board', {
			url: '/board/:boardId/:filter',
			templateUrl: '/board.html',
			controller: 'BoardController',
			params: {
				filter: {value: '', dynamic: true}
			}
		})
		.state('board.detail', {
			url: '/detail/',
			reloadOnSearch: false,
			params: {
				tab: {value: 0, dynamic: true},
			},
			views: {
				'sidebarView': {
					templateUrl: '/board.sidebarView.html'
				}
			}
		})
		.state('board.card', {
			url: '/card/:cardId',
			views: {
				'sidebarView': {
					templateUrl: '/card.sidebarView.html',
					controller: 'CardController'
				}
			}
		});

});
/* global Snap */
app.run(function ($document, $rootScope, $transitions, BoardService) {
	'use strict';
	$document.click(function (event) {
		$rootScope.$broadcast('documentClicked', event);
	});
	$transitions.onEnter({from: 'list'}, function ($state, $transition$) {
		BoardService.unsetCurrrent();
	});
	$transitions.onEnter({to: 'list'}, function ($state, $transition$) {
		BoardService.unsetCurrrent();
		document.title = "Deck - " + oc_defaults.name;
	});
	$transitions.onEnter({to: 'board.card'}, function ($state, $transition$) {
		$rootScope.sidebar.show = true;
	});
	$transitions.onEnter({to: 'board.detail'}, function ($state, $transition$) {
		$rootScope.sidebar.show = true;
	});
	$transitions.onEnter({to: 'board'}, function ($state) {
		$rootScope.sidebar.show = false;
	});
	$transitions.onExit({from: 'board.card'}, function ($state) {
		$rootScope.sidebar.show = false;
	});
	$transitions.onExit({from: 'board.detail'}, function ($state) {
		$rootScope.sidebar.show = false;
	});

	$('link[rel="shortcut icon"]').attr(
		'href',
		OC.filePath('deck', 'img', 'app-512.png')
	);

	$('#app-navigation-toggle').off('click');
	// App sidebar on mobile
	var snapper = new Snap({
		element: document.getElementById('app-content'),
		disable: 'right',
		maxPosition: 250,
		touchToDrag: false
	});

	$('#app-navigation-toggle').click(function () {
		if ($(window).width() > 768) {
			$('#app-navigation').toggle('hidden');
		} else {
			if (snapper.state().state === 'left') {
				snapper.close();
			} else {
				snapper.open('left');
			}
		}
	});
	// Select all elements with data-toggle="tooltips" in the document
	$('body').tooltip({
		selector: '[data-toggle="tooltip"]'
	});

});

/** global: OC */
app.controller('AppController', function ($scope, $location, $http, $route, $log, $rootScope) {
	$rootScope.sidebar = {
		show: false
	};
	$scope.sidebar = $rootScope.sidebar;
	$scope.user = oc_current_user;
});
/* global oc_defaults OC */
app.controller('BoardController', function ($rootScope, $scope, $stateParams, StatusService, BoardService, StackService, CardService, LabelService, $state, $transitions, $filter) {

	$scope.sidebar = $rootScope.sidebar;

	$scope.id = $stateParams.boardId;
	$scope.status = {
		addCard: [],
	};
	$scope.newLabel = {};

	$scope.OC = OC;
	$scope.stackservice = StackService;
	$scope.boardservice = BoardService;
	$scope.cardservice = CardService;
	$scope.statusservice = StatusService.getInstance();
	$scope.labelservice = LabelService;
	$scope.defaultColors = ['31CC7C', '317CCC', 'FF7A66', 'F1DB50', '7C31CC', 'CC317C', '3A3B3D', 'CACBCD'];
	$scope.board = BoardService.getCurrent();

	// workaround for $stateParams changes not being propagated
	$scope.$watch(function() {
		return $state.params;
	}, function (params) {
		$scope.params = params;
	}, true);
	$scope.params = $state;


	$scope.search = function (searchText) {
		$scope.searchText = searchText;
		$scope.refreshData();
	};

	$scope.$watch(function () {
		if (typeof BoardService.getCurrent() !== 'undefined') {
			return BoardService.getCurrent().title;
		} else {
			return null;
		}
	}, function () {
		$scope.setPageTitle();
	});
	$scope.setPageTitle = function () {
		if (BoardService.getCurrent()) {
			document.title = BoardService.getCurrent().title + ' | Deck - ' + oc_defaults.name;
		} else {
			document.title = 'Deck - ' + oc_defaults.name;
		}
	};

	$scope.statusservice.retainWaiting();
	$scope.statusservice.retainWaiting();

	// handle filter parameter for switching between archived/unarchived cards
	$scope.switchFilter = function (filter) {
		$state.go('.', {filter: filter});
	};
	$scope.$watch(function() {
		return $scope.params.filter;
	}, function (filter) {
		if (filter === 'archive') {
			$scope.loadArchived();
		} else {
			$scope.loadDefault();
		}
	});

	$scope.stacksData = StackService;
	$scope.stacks = [];
	$scope.$watch('stacksData', function () {
		$scope.refreshData();
	}, true);
	$scope.refreshData = function () {
		if ($scope.params.filter === 'archive') {
			$scope.filterData('-lastModified', $scope.searchText);
		} else {
			$scope.filterData('order', $scope.searchText);
		}
	};
	$scope.checkCanEdit = function () {
		return !BoardService.getCurrent().archived;
	};

	// filter cards here, as ng-sortable will not work nicely with html-inline filters
	$scope.filterData = function (order, text) {
		if ($scope.stacks === undefined) {
			return;
		}
		angular.copy(StackService.getData(), $scope.stacks);
		$scope.stacks = $filter('orderBy')($scope.stacks, 'order');
		angular.forEach($scope.stacks, function (value, key) {
			var cards = $filter('cardSearchFilter')(value.cards, text);
			cards = $filter('orderBy')(cards, order);
			$scope.stacks[key].cards = cards;
		});
	};

	$scope.loadDefault = function () {
		StackService.fetchAll($scope.id).then(function (data) {
			$scope.statusservice.releaseWaiting();
		}, function (error) {
			$scope.statusservice.setError('Error occured', error);
		});
	};

	$scope.loadArchived = function () {
		StackService.fetchArchived($scope.id).then(function (data) {
			$scope.statusservice.releaseWaiting();
		}, function (error) {
			$scope.statusservice.setError('Error occured', error);
		});
	};

	// Handle initial Loading
	BoardService.fetchOne($scope.id).then(function (data) {
		$scope.statusservice.releaseWaiting();
		$scope.setPageTitle();
	}, function (error) {
		$scope.statusservice.setError('Error occured', error);
	});

	$scope.searchForUser = function (search) {
		BoardService.searchUsers(search);
	};

	$scope.newStack = {'boardId': $scope.id};
	$scope.newCard = {};

	// Create a new Stack
	$scope.createStack = function () {
		StackService.create($scope.newStack).then(function (data) {
			$scope.newStack.title = "";
		});
	};

	$scope.createCard = function (stack, title) {
		var newCard = {
			'title': title,
			'stackId': stack,
			'type': 'plain'
		};
		CardService.create(newCard).then(function (data) {
			$scope.stackservice.addCard(data);
			$scope.newCard.title = "";
		});
	};

	$scope.cardDelete = function (card) {
		OC.dialogs.confirm(t('deck', 'Are you sure you want to delete this card with all of its data?'), t('deck', 'Delete'), function(state) {
			if (!state) {
				return;
			}
			CardService.delete(card.id).then(function () {
				StackService.removeCard(card);
			});
		});
	};
	$scope.cardArchive = function (card) {
		CardService.archive(card);
		StackService.removeCard(card);
	};
	$scope.cardUnarchive = function (card) {
		CardService.unarchive(card);
		StackService.removeCard(card);
	};

	$scope.labelDelete = function (label) {
		LabelService.delete(label.id);
		// remove from board data
		var i = BoardService.getCurrent().labels.indexOf(label);
		BoardService.getCurrent().labels.splice(i, 1);
		// TODO: remove from cards
	};
	$scope.labelCreate = function (label) {
		label.boardId = $scope.id;
		LabelService.create(label).then(function (data) {
			$scope.newStack.title = "";
			BoardService.getCurrent().labels.push(data);
			$scope.status.createLabel = false;
			$scope.newLabel = {};
		});
	};
	$scope.labelUpdate = function (label) {
		label.edit = false;
		LabelService.update(label);
	};

	$scope.aclAdd = function (sharee) {
		sharee.boardId = $scope.id;
		BoardService.addAcl(sharee);
		$scope.status.addSharee = null;
	};
	$scope.aclDelete = function (acl) {
		BoardService.deleteAcl(acl).then(function(data) {
			$scope.loadDefault();
			$scope.refreshData();
		});
	};
	$scope.aclUpdate = function (acl) {
		BoardService.updateAcl(acl);
	};

	$scope.aclTypeString = function (acl) {
		if (typeof acl === 'undefined') {
			return '';
		}
		switch (acl.type) {
			case OC.Share.SHARE_TYPE_USER:
				return 'user';
			case OC.Share.SHARE_TYPE_GROUP:
				return 'group';
			default:
				return '';
		}
	};

	// settings for card sorting
	$scope.sortOptions = {
		id: 'card',
		itemMoved: function (event) {
			event.source.itemScope.modelValue.status = event.dest.sortableScope.$parent.column;
			var order = event.dest.index;
			var card = event.source.itemScope.c;
			var newStack = event.dest.sortableScope.$parent.s.id;
			var oldStack = card.stackId;
			card.stackId = newStack;
			CardService.update(card);
			CardService.reorder(card, order).then(function (data) {
				StackService.addCard(card);
				StackService.reorderCard(card, order);
				StackService.removeCard({
					id: card.id,
					stackId: oldStack
				});
			});
		},
		orderChanged: function (event) {
			var order = event.dest.index;
			var card = event.source.itemScope.c;
			var stack = event.dest.sortableScope.$parent.s.id;
			CardService.reorder(card, order).then(function (data) {
				StackService.reorderCard(card, order);
				$scope.refreshData();
			});
		},
		scrollableContainer: '#innerBoard',
		containerPositioning: 'relative',
		containment: '#innerBoard',
		longTouch: true,
		// auto scroll on drag
		dragMove: function (itemPosition, containment, eventObj) {
			if (eventObj) {
				var container = $("#board");
				var offset = container.offset();
				var targetX = eventObj.pageX - (offset.left || container.scrollLeft());
				var targetY = eventObj.pageY - (offset.top || container.scrollTop());
				if (targetX < offset.left) {
					container.scrollLeft(container.scrollLeft() - 25);
				} else if (targetX > container.width()) {
					container.scrollLeft(container.scrollLeft() + 25);
				}
				if (targetY < offset.top) {
					container.scrollTop(container.scrollTop() - 25);
				} else if (targetY > container.height()) {
					container.scrollTop(container.scrollTop() + 25);
				}
			}
		},
		accept: function (sourceItemHandleScope, destSortableScope, destItemScope) {
			return sourceItemHandleScope.sortableScope.options.id === 'card';
		}
	};

	$scope.sortOptionsStack = {
		id: 'stack',
		orderChanged: function (event) {
			var order = event.dest.index;
			var stack = event.source.itemScope.s;
			StackService.reorder(stack, order).then(function (data) {
				$scope.refreshData();
			});
		},
		scrollableContainer: '#board',
		containerPositioning: 'relative',
		containment: '#innerBoard',
		dragMove: function (itemPosition, containment, eventObj) {
			if (eventObj) {
				var container = $("#board");
				var offset = container.offset();
				var targetX = eventObj.pageX - (offset.left || container.scrollLeft());
				var targetY = eventObj.pageY - (offset.top || container.scrollTop());
				if (targetX < offset.left) {
					container.scrollLeft(container.scrollLeft() - 50);
				} else if (targetX > container.width()) {
					container.scrollLeft(container.scrollLeft() + 50);
				}
				if (targetY < offset.top) {
					container.scrollTop(container.scrollTop() - 50);
				} else if (targetY > container.height()) {
					container.scrollTop(container.scrollTop() + 50);
				}
			}
		},
		accept: function (sourceItemHandleScope, destSortableScope, destItemScope) {
			return sourceItemHandleScope.sortableScope.options.id === 'stack';
		}
	};

	$scope.labelStyle = function (color) {
		return {
			'background-color': '#' + color,
			'color': $filter('textColorFilter')(color)
		};
	};

});

/* global app moment */

app.controller('CardController', function ($scope, $rootScope, $routeParams, $location, $stateParams, $interval, $timeout, $filter, BoardService, CardService, StackService, StatusService) {
	$scope.sidebar = $rootScope.sidebar;
	$scope.status = {
		lastEdit: 0,
		lastSave: Date.now()
	};

	$scope.cardservice = CardService;
	$scope.cardId = $stateParams.cardId;

	$scope.statusservice = StatusService.getInstance();
	$scope.boardservice = BoardService;

	$scope.statusservice.retainWaiting();

	CardService.fetchOne($scope.cardId).then(function (data) {
		$scope.statusservice.releaseWaiting();
		$scope.archived = CardService.getCurrent().archived;
	}, function (error) {
	});

	$scope.cardRenameShow = function () {
		if ($scope.archived || !BoardService.canEdit())
		{return false;}
		else {
			$scope.status.cardRename = true;
		}
	};
	$scope.cardEditDescriptionShow = function ($event) {
		if (BoardService.isArchived() || CardService.getCurrent().archived) {
			return false;
		}
		if ($scope.card.archived || !$scope.boardservice.canEdit()) {
			return false;
		}
		$scope.status.cardEditDescription = true;
		$scope.status.edit = angular.copy(CardService.getCurrent());
		return true;
	};
	$scope.cardEditDescriptionChanged = function ($event) {
		$scope.status.lastEdit = Date.now();
		var header = $('.section-header.card-description');
		header.find('.save-indicator.unsaved').show();
		header.find('.save-indicator.saved').hide();
	};
	$interval(function() {
		var currentTime = Date.now();
		var timeSinceEdit = currentTime-$scope.status.lastEdit;
		if (timeSinceEdit > 1000 && $scope.status.lastEdit > $scope.status.lastSave) {
			$scope.status.lastSave = currentTime;
			var header = $('.section-header.card-description');
			header.find('.save-indicator.unsaved').fadeIn(500);
			CardService.update($scope.status.edit).then(function (data) {
				var header = $('.section-header.card-description');
				header.find('.save-indicator.unsaved').hide();
				header.find('.save-indicator.saved').fadeIn(250).fadeOut(1000);
				StackService.updateCard($scope.status.edit);
			});
		}
	}, 500, 0, false);

	// handle rename to update information on the board as well
	$scope.cardRename = function (card) {
		CardService.rename(card).then(function (data) {
			StackService.updateCard(card);
			$scope.status.renameCard = false;
		});
	};
	$scope.cardUpdate = function (card) {
		CardService.update(card).then(function (data) {
			$scope.status.cardEditDescription = false;
			var header = $('.section-content.card-description');
			header.find('.save-indicator.unsaved').hide();
			header.find('.save-indicator.saved').fadeIn(500).fadeOut(1000);
			StackService.updateCard(card);
		});
	};

	$scope.labelAssign = function (element, model) {
		CardService.assignLabel($scope.cardId, element.id).then(function (data) {
			StackService.updateCard(CardService.getCurrent());
		});
	};

	$scope.labelRemove = function (element, model) {
		CardService.removeLabel($scope.cardId, element.id).then(function (data) {
			StackService.updateCard(CardService.getCurrent());
		});
	};

	$scope.setDuedate = function (duedate) {
		var element = CardService.getCurrent();
		var newDate = moment(element.duedate);
		if(!newDate.isValid()) {
			newDate = moment();
		}
		newDate.date(duedate.date());
		newDate.month(duedate.month());
		newDate.year(duedate.year());
		element.duedate = newDate.toISOString();
		CardService.update(element);
		StackService.updateCard(element);
	};
	$scope.setDuedateTime = function (time) {
		var element = CardService.getCurrent();
		var newDate = moment(element.duedate);
		if(!newDate.isValid()) {
			newDate = moment();
		}
		newDate.hour(time.hour());
		newDate.minute(time.minute());
		element.duedate = newDate.toISOString();
		CardService.update(element);
		StackService.updateCard(element);
	};

	$scope.resetDuedate = function () {
		var element = CardService.getCurrent();
		element.duedate = null;
		CardService.update(element);
		StackService.updateCard(element);
	};
	
	/**
	 * Show ui-select field when clicking the add button
	 */
	$scope.toggleAssignUser = function() {
		$scope.status.showAssignUser = !$scope.status.showAssignUser;
		if ($scope.status.showAssignUser === true) {
			$timeout(function () {
				$('#assignUserSelect').find('a').click();
			});
		}
	};

	/**
	 * Hide ui-select when select list is closed
	 */
	$scope.assingUserOpenClose = function(isOpen) {
		$scope.status.showAssignUser = isOpen;
	};

	$scope.addAssignedUser = function(item) {
		CardService.assignUser(CardService.getCurrent(), item.uid).then(function (data) {
			StackService.updateCard(CardService.getCurrent());
		});
		$scope.status.showAssignUser = false;
	};

	$scope.removeAssignedUser = function(uid) {
		CardService.unassignUser(CardService.getCurrent(), uid).then(function (data) {
			StackService.updateCard(CardService.getCurrent());
		});
	};

	$scope.labelStyle = function (color) {
		return {
			'background-color': '#' + color,
			'color': $filter('textColorFilter')(color)
		};
	};

});
/* global app angular */

app.controller('ListController', function ($scope, $location, $filter, BoardService, $element, $timeout, $stateParams, $state, StatusService) {

	function calculateNewColor() {
		var boards = BoardService.getAll();
		var boardKeys = Object.keys(boards);
		var colorOccurrences = [];

		for (var i = 0; i < $scope.colors.length; i++) {
			colorOccurrences.push(0);
		}

		for (var j = 0; j < boardKeys.length; j++) {
			var key = boardKeys[j];
			var board = boards[key];

			if (board && $scope.colors.indexOf(board.color) !== -1) {
				colorOccurrences[$scope.colors.indexOf(board.color)]++;
			}
		}

		return $scope.colors[colorOccurrences.indexOf(Math.min.apply(Math, colorOccurrences))];
	}

	$scope.boards = [];
	$scope.newBoard = {};
	$scope.status = {
		deleteUndo: [],
		filter: $stateParams.filter ? $stateParams.filter : '',
		sidebar: false
	};
	$scope.colors = ['0082c9', '00c9c6','00c906', 'c92b00', 'F1DB50', '7C31CC', '3A3B3D', 'CACBCD'];
	$scope.boardservice = BoardService;
	$scope.updatingBoard = null;

	var filterData = function () {
		if($element.attr('id') === 'app-navigation') {
			$scope.boardservice.sidebar = $scope.boardservice.getData();
			$scope.boardservice.sidebar = $filter('orderBy')($scope.boardservice.sidebar, 'title');
			$scope.boardservice.sidebar = $filter('cardFilter')($scope.boardservice.sidebar, {archived: false});
		} else {
			$scope.boardservice.sorted = $scope.boardservice.getData();
			if ($scope.status.filter === 'archived') {
				var filter = {};
				filter[$scope.status.filter] = true;
				$scope.boardservice.sorted = $filter('cardFilter')($scope.boardservice.sorted, filter);
			} else if ($scope.status.filter === 'shared') {
				$scope.boardservice.sorted = $filter('cardFilter')($scope.boardservice.sorted, {archived: false});
				$scope.boardservice.sorted = $filter('boardFilterAcl')($scope.boardservice.sorted);
			} else {
				$scope.boardservice.sorted = $filter('cardFilter')($scope.boardservice.sorted, {archived: false});
			}
			$scope.boardservice.sorted = $filter('orderBy')($scope.boardservice.sorted, ['deletedAt', 'title']);
		}
	};

	var finishedLoading = function() {
		filterData();
		$scope.newBoard.color = calculateNewColor();
	};

	var initialize = function () {
		$scope.statusservice = StatusService.listStatus;

		if($element.attr('id') === 'app-navigation') {
			$scope.statusservice.retainWaiting();
			BoardService.fetchAll().then(function(data) {
				finishedLoading();
				$scope.statusservice.releaseWaiting();
				BoardService.loaded = true;
			}, function (error) {
				$scope.statusservice.setError('Error occured', error);
			});
		} else {
			/* initialize main list controller when board list is loaded */
			var boardDataWatch = $scope.$watch(function () {
				return $scope.boardservice.loaded;
			}, function () {
				if (BoardService.loaded === true) {
					boardDataWatch();
					finishedLoading();
				}
			});
		}

		$scope.$watch(function () {
			return $scope.boardservice.data;
		}, function () {
			filterData();
		}, true);

		/* Watch for board filter change */
		$scope.$watchCollection(function(){
			return $state.params;
		}, function(){
			$scope.status.filter = $state.params.filter;
			filterData();
		});
	};
	initialize();

	$scope.selectColor = function(color) {
		$scope.newBoard.color = color;
	};

	$scope.gotoBoard = function(board) {
		if(board.deletedAt > 0) {
			return false;
		}
		return $state.go('board', {boardId: board.id});
	};

	$scope.boardCreate = function() {
		if(!$scope.newBoard.title || !$scope.newBoard.color) {
			$scope.status.addBoard=false;
			return;
		}
		BoardService.create($scope.newBoard)
			.then(function (response) {
				$scope.newBoard = {};
				$scope.newBoard.color = calculateNewColor();
				$scope.status.addBoard=false;
				filterData();
			}, function(error) {
				$scope.status.createBoard = 'Unable to insert board: ' + error.message;
			});
	};

	$scope.boardUpdate = function(board) {
		BoardService.update(board).then(function(data) {
			board.status.edit = false;
			filterData();
		});
	};

	$scope.boardUpdateBegin = function(board) {
		$scope.updatingBoard = angular.copy(board);
	};

	$scope.boardUpdateReset = function(board) {
		board.title = $scope.updatingBoard.title;
		board.color = $scope.updatingBoard.color;
		filterData();
		board.status.edit = false;
	};

	$scope.boardArchive = function (board) {
		board.archived = true;
		BoardService.update(board).then(function(data) {
			filterData();
		});
	};

	$scope.boardUnarchive = function (board) {
		board.archived = false;
		BoardService.update(board).then(function(data) {
			filterData();
		});
	};

	$scope.boardDelete = function(board) {
		BoardService.delete(board.id).then(function (data) {
			filterData();
		});
	};

	$scope.boardDeleteUndo = function (board) {
		BoardService.deleteUndo(board.id).then(function (data) {
			filterData();
		});
	};

});


app.filter('boardFilterAcl', function() {
	return function(boards) {
		var _result = [];
		angular.forEach(boards, function(board){
			if(board.acl !== null && Object.keys(board.acl).length > 0) {
				_result.push(board);
			}
		});
		return _result;
	};
});
// usage | cardFilter({ member: 'admin'})

app.filter('cardFilter', function() {
	return function(cards, rules) {
		var _result = [];
		angular.forEach(cards, function(card){
			var _card = card;
			var keys = Object.keys(rules);
			keys.some(function(key, condition) {
				if(_card[key]===rules[key]) {
					_result.push(_card);
				}
			});
		});
		return _result;
	};
});
app.filter('cardSearchFilter', function() {
	return function(cards, searchString) {
		var _result = {};
		var rules = {
			title: searchString,
			//owner: searchString,
		};
		angular.forEach(cards, function(card){
			var _card = card;
			Object.keys(rules).some(function(rule) {
				if(_card[rule].search(rules[rule])>=0) {
					_result[_card.id] = _card;
				}
			});
		});

		return $.map(_result, function(value, index) {
			return [value];
		});
	};
});
/* global app */
/* global OC */
/* global moment */

app.filter('relativeDateFilter', function() {
	return function (timestamp) {
		return OC.Util.relativeModifiedDate(timestamp*1000);
	};
});

app.filter('relativeDateFilterString', function() {
	return function (date) {
		return OC.Util.relativeModifiedDate(Date.parse(date));
	};
});

app.filter('dateToTimestamp', function() {
	return function (date) {
		return Date.parse(date);
	};
});

app.filter('parseDate', function() {
	return function (date) {
		if(moment(date).isValid()) {
			return moment(date).format('YYYY-MM-DD');
		}
		return '';
	};
});

app.filter('parseTime', function() {
	return function (date) {
		if(moment(date).isValid()) {
			return moment(date).format('HH:mm');
		}
		return '';
	};
});
app.filter('iconWhiteFilter', function () {
	return function (hex) {
		// RGB2HLS by Garry Tan
		// http://axonflux.com/handy-rgb-to-hsl-and-rgb-to-hsv-color-model-c
		var result = /^([A-Fa-f\d]{2})([A-Fa-f\d]{2})([A-Fa-f\d]{2})$/i.exec(hex);
		var color = result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
		if (result === null) {
			return "";
		}
		var r = color.r / 255;
		var g = color.g / 255;
		var b = color.b / 255;
		var max = Math.max(r, g, b), min = Math.min(r, g, b);
		var h, s, l = (max + min) / 2;

		if (max === min) {
			h = s = 0; // achromatic
		} else {
			var d = max - min;
			s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
			switch (max) {
				case r:
					h = (g - b) / d + (g < b ? 6 : 0);
					break;
				case g:
					h = (b - r) / d + 2;
					break;
				case b:
					h = (r - g) / d + 4;
					break;
			}
			h /= 6;
		}
		if (l < 0.5) {
			return "-white";
		} else {
			return "";
		}
	};
});

app.filter('lightenColorFilter', function() {
	return function (hex) {
		var result = /^([A-Fa-f\d]{2})([A-Fa-f\d]{2})([A-Fa-f\d]{2})$/i.exec(hex);
		var color = result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
		if (result !== null) {
			return "rgba(" + color.r + "," + color.g + "," + color.b + ",0.7)";
		} else {
			return "#" + hex;
		}
	};
});
app.filter('orderObjectBy', function(){
	return function(input, attribute) {
		if (!angular.isObject(input)) {
			return input;
		}
		var array = [];
		for(var objectKey in input) {
			if ({}.hasOwnProperty.call(input, objectKey)) {
				array.push(input[objectKey]);
			}
		}

		array.sort(function(a, b){
			a = parseInt(a[attribute]);
			b = parseInt(b[attribute]);
			return a < b;
		});
		return array;
	};
});
app.filter('textColorFilter', function () {
	return function (hex) {
		// RGB2HLS by Garry Tan
		// http://axonflux.com/handy-rgb-to-hsl-and-rgb-to-hsv-color-model-c
		var result = /^#?([A-Fa-f\d]{2})([A-Fa-f\d]{2})([A-Fa-f\d]{2})$/i.exec(hex);
		var color = result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
		if (result !== null) {
			var r = color.r / 255;
			var g = color.g / 255;
			var b = color.b / 255;
			var max = Math.max(r, g, b), min = Math.min(r, g, b);
			var h, s, l = (max + min) / 2;

			if (max === min) {
				h = s = 0; // achromatic
			} else {
				var d = max - min;
				s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
				switch (max) {
					case r:
						h = (g - b) / d + (g < b ? 6 : 0);
						break;
					case g:
						h = (b - r) / d + 2;
						break;
					case b:
						h = (r - g) / d + 4;
						break;
				}
				h /= 6;
			}
			if (l < 0.5) {
				return "#ffffff";
			} else {
				return "#000000";
			}
		} else {
			return "#000000";
		}

	};
});

/* global app */
/* global angular */

/*
 * Remove all assignedUsers from users list
 */
app.filter('withoutAssignedUsers', function () {
	return function (users, assignedUsers) {
		var _result = [];
		angular.forEach(users, function (user) {
			var _found = false;
			angular.forEach(assignedUsers, function (assignedUser) {
				if (assignedUser.participant.uid === user.uid) {
					_found = true;
				}
			});
			if (_found === false) {
				_result.push(user);
			}
		});
		return _result;
	};
});
// OwnCloud Click Handling
// https://doc.owncloud.org/server/8.0/developer_manual/app/css.html
app.directive('appNavigationEntryUtils', function () {
	'use strict';
	return {
		restrict: 'C',
		link: function (scope, elm) {

			var menu = elm.siblings('.app-navigation-entry-menu');
			var button = $(elm)
				.find('.app-navigation-entry-utils-menu-button button');

			button.click(function () {
				menu.toggleClass('open');
			});
			scope.$on('documentClicked', function (scope, event) {
				if (event.target !== button[0]) {
					menu.removeClass('open');
				}
			});
		}
	};
});


app.directive('appPopoverMenuUtils', function () {
	'use strict';
	return {
		restrict: 'C',
		link: function (scope, elm) {
			var menu = elm.find('.popovermenu');
			var button = elm.find('button');
			button.click(function (e) {
				var popovermenus = $('.popovermenu');
				var shouldShow = menu.hasClass('hidden');
				popovermenus.addClass('hidden');
				if (shouldShow) {
					menu.toggleClass('hidden');
				}
				e.stopPropagation();
			});
			scope.$on('documentClicked', function (scope, event) {
				/* prevent closing popover if target has no-close class */
				if (event.target !== button && !$(event.target).hasClass('no-close')) {
					menu.addClass('hidden');
				}
			});
		}
	};
});

app.directive('autofocusOnInsert', function () {
	'use strict';
	return function (scope, elm) {
		elm.focus();
	};
});
app.directive('avatar', function() {
	'use strict';
	return {
		restrict: 'AEC',
		transclude: true,
		replace: true,
		template: '<div class="avatardiv-container"><div class="avatardiv" data-toggle="tooltip" ng-transclude></div></div>',
		scope: { attr: '=' },
		link: function(scope, element, attr){
			scope.uid = attr.displayname;
			scope.displayname = attr.displayname;
			var value = attr.user;
			var avatardiv = $(element).find('.avatardiv');
			if(typeof attr.contactsmenu !== 'undefined' && attr.contactsmenu !== 'false') {
				avatardiv.contactsMenu(value, 0, $(element));
				avatardiv.addClass('has-contactsmenu');
			}
			if(typeof attr.tooltip !== 'undefined' && attr.tooltip !== 'false') {
				$(element).tooltip({
					title: scope.displayname,
					placement: 'top'
				});
			}
			avatardiv.avatar(value, 32, false, false, false, attr.displayname);
		},
		controller: function () {}
	};
});
app.directive('contactsmenudelete', function() {
	'use strict';
	return {
		restrict: 'A',
		priority: 1,
		link: function(scope, element, attr){
			var user = attr.user;
			var menu = $(element).parent().find('.contactsmenu-popover');
			if (oc_current_user === user) {
				menu.children(':first').remove();
			}
			var menuEntry = $('<li><a><span class="icon icon-delete"></span><span>' + t('deck', 'Remove user from card') + '</span></a></li>');
			menuEntry.on('click', function () {
				scope.removeAssignedUser(user);
			});
			$(menu).append(menuEntry);
		}
	};
});
/* global app */
/* gloabl t */
/* global moment */

app.directive('datepicker', function () {
	'use strict';
	return {
		link: function (scope, elm, attr) {
			return elm.datepicker({
				dateFormat: 'yy-mm-dd',
				onSelect: function(date, inst) {
					scope.setDuedate(moment(date));
					scope.$apply();
				},
				beforeShow: function(input, inst) {
					var dp, marginLeft;
					dp = $(inst).datepicker('widget');
					marginLeft = -Math.abs($(input).outerWidth() - dp.outerWidth()) / 2 + 'px';
					dp.css({
						'margin-left': marginLeft
					});
					$('div.ui-datepicker:before').css({
						'left': 100 + 'px'
					});
					return $('.hasDatepicker').datepicker();
				},
				minDate: null
			});
		}
	};
});
// original idea from blockloop: http://stackoverflow.com/a/24090733
app.directive('elastic', [
	'$timeout',
	function($timeout) {
		return {
			restrict: 'A',
			link: function($scope, element) {
				$scope.initialHeight = $scope.initialHeight || element[0].style.height;
				var resize = function() {
					element[0].style.height = $scope.initialHeight;
					element[0].style.height = "" + element[0].scrollHeight + "px";
				};
				element.on("input change", resize);
				$timeout(resize, 0);
			}
		};
	}
]);
app.directive('search', function ($document, $location) {
	'use strict';

	return {
		restrict: 'E',
		scope: {
			'onSearch': '='
		},
		link: function (scope) {
			var box = $('#searchbox');
			box.val($location.search().search);

			var doSearch = function() {
				var value = box.val();
				scope.$apply(function () {
					scope.onSearch(value);
				});
			};

			box.on('search keyup', function (event) {
				if (event.type === 'search' || event.keyCode === 13 ) {
					doSearch();
				}
			});

		}
	};
});

/* global app */
/* global t */
/* global moment */

app.directive('timepicker', function() {
	'use strict';
	return {
		restrict: 'A',
		link: function(scope, elm, attr) {
			return elm.timepicker({
				onSelect: function(date, inst) {
					scope.setDuedateTime(moment('2000-01-01 ' + date));
					scope.$apply();
				},
				myPosition: 'center top',
				atPosition: 'center bottom',
				hourText: t('deck', 'Hours'),
				minuteText: t('deck', 'Minutes'),
				showPeriodLabels: false
			});
		}
	};
});

/** global: oc_defaults */
app.factory('ApiService', function ($http, $q) {
	var ApiService = function (http, endpoint) {
		this.endpoint = endpoint;
		this.baseUrl = OC.generateUrl('/apps/deck/' + endpoint);
		this.http = http;
		this.q = $q;
		this.data = {};
		this.id = null;
		this.sorted = [];
	};

	ApiService.prototype.fetchAll = function () {
		var deferred = $q.defer();
		var self = this;
		$http.get(this.baseUrl).then(function (response) {
			var objects = response.data;
			objects.forEach(function (obj) {
				self.data[obj.id] = obj;
			});
			deferred.resolve(self.data);
		}, function (error) {
			deferred.reject('Fetching ' + self.endpoint + ' failed');
		});
		return deferred.promise;
	};

	ApiService.prototype.fetchOne = function (id) {

		this.id = id;
		var deferred = $q.defer();

		if (id === undefined) {
			return deferred.promise;
		}

		var self = this;
		$http.get(this.baseUrl + '/' + id).then(function (response) {
			var data = response.data;
			if (self.data[data.id] === undefined) {
				self.data[data.id] = response.data;
			}
			$.each(response.data, function (key, value) {
				self.data[data.id][key] = value;
			});
			deferred.resolve(response.data);

		}, function (error) {
			deferred.reject('Fetching ' + self.endpoint + ' failed');
		});
		return deferred.promise;
	};

	ApiService.prototype.create = function (entity) {
		var deferred = $q.defer();
		var self = this;
		$http.post(this.baseUrl, entity).then(function (response) {
			self.add(response.data);
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Fetching' + self.endpoint + ' failed');
		});
		return deferred.promise;
	};

	ApiService.prototype.update = function (entity) {
		var deferred = $q.defer();
		var self = this;
		$http.put(this.baseUrl + '/' + entity.id, entity).then(function (response) {
			self.add(response.data);
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Updating ' + self.endpoint + ' failed');
		});
		return deferred.promise;

	};

	ApiService.prototype.delete = function (id) {
		var deferred = $q.defer();
		var self = this;

		$http.delete(this.baseUrl + '/' + id).then(function (response) {
			self.remove(id);
			deferred.resolve(response.data);

		}, function (error) {
			deferred.reject('Deleting ' + self.endpoint + ' failed');
		});
		return deferred.promise;

	};


	// methods for managing data
	ApiService.prototype.clear = function () {
		this.data = {};
	};
	ApiService.prototype.add = function (entity) {
		var element = this.data[entity.id];
		if (element === undefined) {
			this.data[entity.id] = entity;
		} else {
			Object.keys(entity).forEach(function (key) {
				if (entity[key] !== null) {
					element[key] = entity[key];
				}
			});
			element.status = {};
		}
	};
	ApiService.prototype.remove = function (id) {
		if (this.data[id] !== undefined) {
			delete this.data[id];
		}
	};
	ApiService.prototype.addAll = function (entities) {
		var self = this;
		angular.forEach(entities, function (entity) {
			self.add(entity);
		});
	};

	ApiService.prototype.getCurrent = function () {
		return this.data[this.id];
	};

	ApiService.prototype.unsetCurrrent = function () {
		this.id = null;
	};


	ApiService.prototype.getData = function () {
		return $.map(this.data, function (value, index) {
			return [value];
		});
	};

	ApiService.prototype.getAll = function () {
		return this.data;
	};

	ApiService.prototype.getName = function () {
		var funcNameRegex = /function (.{1,})\(/;
		var results = (funcNameRegex).exec((this).constructor.toString());
		return (results && results.length > 1) ? results[1] : "";
	};

	return ApiService;

});

/* global app OC */
app.factory('BoardService', function (ApiService, $http, $q) {
	var BoardService = function ($http, ep, $q) {
		ApiService.call(this, $http, ep, $q);
	};
	BoardService.prototype = angular.copy(ApiService.prototype);

	BoardService.prototype.delete = function (id) {
		var deferred = $q.defer();
		var self = this;

		$http.delete(this.baseUrl + '/' + id).then(function (response) {
			self.data[id].deletedAt = response.data.deletedAt;
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Deleting ' + self.endpoint + ' failed');
		});
		return deferred.promise;
	};

	BoardService.prototype.deleteUndo = function (id) {
		var deferred = $q.defer();
		var self = this;
		var _id = id;
		$http.post(this.baseUrl + '/' + id + '/deleteUndo').then(function (response) {
			self.data[_id].deletedAt = 0;
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Deleting ' + self.endpoint + ' failed');
		});
		return deferred.promise;
	};

	BoardService.prototype.searchUsers = function (search) {
		var deferred = $q.defer();
		var self = this;
		var searchData = {
			format: 'json',
			perPage: 4,
			itemType: [0, 1]
		};
		if (search !== "") {
			searchData.search = search;
		}
		$http({
			method: 'GET',
			url: OC.linkToOCS('apps/files_sharing/api/v1') + 'sharees',
			params: searchData
		})
			.then(function (result) {
				var response = result.data;
				if (response.ocs.meta.statuscode !== 100) {
					deferred.reject('Error while searching for sharees');
					return;
				}
				self.sharees = [];

				var users = response.ocs.data.exact.users.concat(response.ocs.data.users.slice(0, 4));
				var groups = response.ocs.data.exact.groups.concat(response.ocs.data.groups.slice(0, 4));

				// filter out everyone who is already in the share list
				angular.forEach(users, function (item) {
					var acl = self.generateAcl(OC.Share.SHARE_TYPE_USER, item);
					var exists = false;
					angular.forEach(self.getCurrent().acl, function (acl) {
						if (acl.participant.primaryKey === item.value.shareWith) {
							exists = true;
						}
					});
					if (!exists && OC.getCurrentUser().uid !== item.value.shareWith) {
						self.sharees.push(acl);
					}
				});
				angular.forEach(groups, function (item) {
					var acl = self.generateAcl(OC.Share.SHARE_TYPE_GROUP, item);
					var exists = false;
					angular.forEach(self.getCurrent().acl, function (acl) {
						if (acl.participant.primaryKey === item.value.shareWith) {
							exists = true;
						}
					});
					if (!exists) {
						self.sharees.push(acl);
					}
				});

				deferred.resolve(self.sharees);
			}, function () {
				deferred.reject('Error while searching for sharees');
			});

		return deferred.promise;
	};

	BoardService.prototype.generateAcl = function (type, ocsItem) {
		return {
			boardId: null,
			id: null,
			owner: false,
			participant: {
				primaryKey: ocsItem.value.shareWith,
				uid: ocsItem.value.shareWith,
				displayname: ocsItem.label
			},
			permissionEdit: true,
			permissionManage: true,
			permissionShare: true,
			type: type
		};
	};

	BoardService.prototype.addAcl = function (acl) {
		var board = this.getCurrent();
		var deferred = $q.defer();
		var self = this;
		var _acl = acl;
		$http.post(this.baseUrl + '/' + acl.boardId + '/acl', _acl).then(function (response) {
			if (!board.acl || board.acl.length === 0) {
				board.acl = {};
			}
			board.acl[response.data.id] = response.data;
			if (response.data.type === OC.Share.SHARE_TYPE_USER) {
				self._updateUsers();
			} else {
				self.fetchOne(response.data.boardId);
			}
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error creating ACL ' + _acl);
		});
		acl = null;
		return deferred.promise;
	};

	BoardService.prototype.deleteAcl = function (acl) {
		var board = this.getCurrent();
		var deferred = $q.defer();
		var self = this;
		$http.delete(this.baseUrl + '/' + acl.boardId + '/acl/' + acl.id).then(function (response) {
			delete board.acl[response.data.id];
			if (response.data.type === OC.Share.SHARE_TYPE_USER) {
				self._updateUsers();
			} else {
				self.fetchOne(response.data.boardId);
			}
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error deleting ACL ' + acl.id);
		});
		acl = null;
		return deferred.promise;
	};

	BoardService.prototype.updateAcl = function (acl) {
		var board = this.getCurrent();
		var deferred = $q.defer();
		var self = this;
		var _acl = acl;
		$http.put(this.baseUrl + '/' + acl.boardId + '/acl', _acl).then(function (response) {
			board.acl[_acl.id] = response.data;
			if (response.data.type === OC.Share.SHARE_TYPE_USER) {
				self._updateUsers();
			} else {
				self.fetchOne(response.data.boardId);
			}
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error updating ACL ' + _acl);
		});
		acl = null;
		return deferred.promise;
	};

	BoardService.prototype._updateUsers = function () {
		if (!this.getCurrent() || !this.getCurrent().acl) {
			return [];
		}

		var result = [this.getCurrent().owner];
		angular.forEach(this.getCurrent().acl, function(value, key) {
			if (value.type === OC.Share.SHARE_TYPE_USER) {
				result.push(value.participant);
			}
		});
		this.getCurrent().users = result;
	};

	BoardService.prototype.getUsers = function () {
		if (this.getCurrent() && !this.getCurrent().users) {
			this._updateUsers();
		}
		return this.getCurrent().users;
	};

	BoardService.prototype.canRead = function () {
		if (!this.getCurrent() || !this.getCurrent().permissions) {
			return false;
		}
		return this.getCurrent().permissions['PERMISSION_READ'];
	};

	BoardService.prototype.canEdit = function () {
		if (!this.getCurrent() || !this.getCurrent().permissions) {
			return false;
		}
		return this.getCurrent().permissions['PERMISSION_EDIT'];
	};

	BoardService.prototype.canManage = function (board) {
		if (board !== null && board !== undefined) {
			return board.permissions['PERMISSION_MANAGE'];
		}
		if (!this.getCurrent() || !this.getCurrent().permissions) {
			return false;
		}
		return this.getCurrent().permissions['PERMISSION_MANAGE'];
	};

	BoardService.prototype.canShare = function () {
		if (!this.getCurrent() || !this.getCurrent().permissions) {
			return false;
		}
		return this.getCurrent().permissions['PERMISSION_SHARE'];
	};

	BoardService.prototype.isArchived = function () {
		if (!this.getCurrent() || this.getCurrent().archived) {
			return true;
		}
		return false;
	};

	return new BoardService($http, 'boards', $q);

});

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
app.factory('LabelService', function (ApiService, $http, $q) {
	var LabelService = function ($http, ep, $q) {
		ApiService.call(this, $http, ep, $q);
	};
	LabelService.prototype = angular.copy(ApiService.prototype);
	return new LabelService($http, 'labels', $q);
});
app.factory('StackService', function (ApiService, $http, $q) {
	var StackService = function ($http, ep, $q) {
		ApiService.call(this, $http, ep, $q);
	};
	StackService.prototype = angular.copy(ApiService.prototype);
	StackService.prototype.fetchAll = function (boardId) {
		var deferred = $q.defer();
		var self = this;
		$http.get(this.baseUrl + '/' + boardId).then(function (response) {
			self.clear();
			self.addAll(response.data);
			deferred.resolve(self.data);
		}, function (error) {
			deferred.reject('Error while loading stacks');
		});
		return deferred.promise;
	};

	StackService.prototype.fetchArchived = function (boardId) {
		var deferred = $q.defer();
		var self = this;
		$http.get(this.baseUrl + '/' + boardId + '/archived').then(function (response) {
			self.clear();
			self.addAll(response.data);
			deferred.resolve(self.data);
		}, function (error) {
			deferred.reject('Error while loading stacks');
		});
		return deferred.promise;
	};

	StackService.prototype.addCard = function (entity) {
		if (!this.data[entity.stackId].cards) {
			this.data[entity.stackId].cards = [];
		}
		this.data[entity.stackId].cards.push(entity);
	};

	StackService.prototype.reorder = function (stack, order) {
		var deferred = $q.defer();
		var self = this;
		$http.put(this.baseUrl + '/' + stack.id + '/reorder', {
			stackId: stack.id,
			order: order
		}).then(function (response) {
			angular.forEach(response.data, function (value, key) {
				var id = value.id;
				self.data[id].order = value.order;
			});
			deferred.resolve(response.data);
		}, function (error) {
			deferred.reject('Error while update ' + self.endpoint);
		});
		return deferred.promise;
	};

	StackService.prototype.reorderCard = function (entity, order) {
		// assign new order
		for (var i = 0, j = 0; i < this.data[entity.stackId].cards.length; i++) {
			if (this.data[entity.stackId].cards[i].id === entity.id) {
				this.data[entity.stackId].cards[i].order = order;
			}
			if (j === order) {
				j++;
			}
			if (this.data[entity.stackId].cards[i].id !== entity.id) {
				this.data[entity.stackId].cards[i].order = j++;
			}
		}
		// sort array by order
		this.data[entity.stackId].cards.sort(function (a, b) {
			if (a.order < b.order)
			{return -1;}
			if (a.order > b.order)
			{return 1;}
			return 0;
		});
	};

	StackService.prototype.updateCard = function (entity) {
		var self = this;
		var cards = this.data[entity.stackId].cards;
		for (var i = 0; i < cards.length; i++) {
			if (cards[i].id === entity.id) {
				cards[i] = entity;
			}
		}
	};
	StackService.prototype.removeCard = function (entity) {
		var self = this;
		var cards = this.data[entity.stackId].cards;
		for (var i = 0; i < cards.length; i++) {
			if (cards[i].id === entity.id) {
				cards.splice(i, 1);
			}
		}
	};

	// FIXME: Should not sure popup but proper undo mechanism
	StackService.prototype.delete = function (id) {
		var deferred = $q.defer();
		var self = this;

		OC.dialogs.confirm(t('deck', 'Are you sure you want to delete the stack with all of its data?'), t('deck', 'Delete'), function(state) {
			if (!state) {
				return;
			}
			$http.delete(self.baseUrl + '/' + id).then(function (response) {
				self.remove(id);
				deferred.resolve(response.data);

			}, function (error) {
				deferred.reject('Deleting ' + self.endpoint + ' failed');
			});
		});
		return deferred.promise;
	};

	var service = new StackService($http, 'stacks', $q);
	return service;
});


app.factory('StatusService', function () {
	// Status Helper
	var StatusService = function () {
		this.active = true;
		this.icon = 'loading';
		this.title = '';
		this.text = '';
		this.counter = 0;
	};


	StatusService.prototype.setStatus = function ($icon, $title, $text) {
		this.active = true;
		this.icon = $icon;
		this.title = $title;
		this.text = $text;
	};

	StatusService.prototype.setError = function ($title, $text) {
		this.active = true;
		this.icon = 'error';
		this.title = $title;
		this.text = $text;
		this.counter = 0;
	};

	StatusService.prototype.releaseWaiting = function () {
		if (this.counter > 0) {
			this.counter--;
		}
		if (this.counter <= 0) {
			this.active = false;
			this.counter = 0;
		}
	};

	StatusService.prototype.retainWaiting = function () {
		this.active = true;
		this.icon = 'loading';
		this.title = '';
		this.text = '';
		this.counter++;
	};

	StatusService.prototype.unsetStatus = function () {
		this.active = false;
	};

	return {
		getInstance: function () {
			return new StatusService();
		},
		/* Shared StatusService instance between both ListController instances */
		listStatus: new StatusService()
	};

});



