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

namespace OCA\Mindmaps\AppInfo;

use OCA\Mindmaps\Db\{AclMapper, MindmapMapper};
use OCA\Mindmaps\Util;
use OCP\{IGroup, IGroupManager, IUser, IUserManager, Share};
use OCP\AppFramework\App;
use Symfony\Component\EventDispatcher\{EventDispatcher, GenericEvent};

class Application extends App {

	// App name.
	const APP_NAME = 'mindmaps';
	// The used table names.
	const  MINDMAPS_TABLE = 'mindmaps';
	const  MINDMAPS_NODES_TABLE = 'mindmaps_nodes';
	const  MINDMAPS_ACL_TABLE = 'mindmaps_acl';

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams
	 *
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function __construct(array $urlParams = array()) {
		parent::__construct(static::APP_NAME, $urlParams);

		/** @var \OCP\AppFramework\IAppContainer $container */
		$container = $this->getContainer();
		/** @var \OCP\IServerContainer $server */
		$server = $container->getServer();
		/** @var AclMapper $aclMapper */
		$aclMapper = $container->query(AclMapper::class);
		/** @var MindmapMapper $mindmapMapper */
		$mindmapMapper = $container->query(MindmapMapper::class);

		// Delete user acl entries when they get deleted
		/** @var IUserManager $userManager */
		$userManager = $server->getUserManager();
		$userManager->listen('\OC\User', 'postDelete', function (IUser $user) use ($aclMapper, $mindmapMapper) {
			$acls = $aclMapper->findByParticipant(Share::SHARE_TYPE_USER, $user->getUID());
			foreach ($acls as $acl) {
				$aclMapper->delete($acl);
			}
			$mindmaps = $mindmapMapper->findAll($user->getUID());
			foreach ($mindmaps as $mindmap) {
				$mindmapMapper->delete($mindmap);
			}
		});

		// Delete group acl entries when they get deleted
		/** @var IGroupManager $userManager */
		$groupManager = $server->getGroupManager();
		$groupManager->listen('\OC\Group', 'postDelete', function (IGroup $group) use ($aclMapper) {
			$acls = $aclMapper->findByParticipant(Share::SHARE_TYPE_GROUP, $group->getGID());
			foreach ($acls as $acl) {
				$aclMapper->delete($acl);
			}
		});

		if (Util::isCirclesAppEnabled()) {
			// Delete circle acl entries when they get deleted
			/** @var EventDispatcher $dispatcher */
			$dispatcher = $container->query(EventDispatcher::class);
			$dispatcher->addListener(
				'\OCA\Circles::onCircleDestruction',
				function (GenericEvent $event) use ($aclMapper) {
					/** @var \OCA\Circles\Model\Circle $circle */
					$circle = $event['circle'];
					if ($circle !== null) {
						$acls = $aclMapper->findByParticipant(Share::SHARE_TYPE_CIRCLE, $circle->getUniqueId());
						foreach ($acls as $acl) {
							$aclMapper->delete($acl);
						}
					}
				}
			);
		}
	}

	/**
	 * Register navigation entry for main navigation.
	 *
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function registerNavigationEntry() {
		$container = $this->getContainer();
		$container->query('OCP\INavigationManager')->add(function () use ($container) {
			$urlGenerator = $container->query('OCP\IURLGenerator');
			$l10n = $container->query('OCP\IL10N');
			return [
				'id' => static::APP_NAME,
				'order' => 10,
				'href' => $urlGenerator->linkToRoute(static::APP_NAME . '.page.index'),
				'icon' => $urlGenerator->imagePath(static::APP_NAME, 'app.svg'),
				'name' => $l10n->t('Mindmaps')
			];
		});
	}
}
