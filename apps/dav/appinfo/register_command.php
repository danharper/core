<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
use OCA\Dav\AppInfo\Application;
use OCA\DAV\Command\CreateAddressBook;
use OCA\DAV\Command\CreateCalendar;
use OCA\Dav\Command\MigrateAddressbooks;
use OCA\Dav\Command\MigrateCalendars;
use OCA\DAV\Command\SyncSystemAddressBook;

$dbConnection = \OC::$server->getDatabaseConnection();
$userManager = OC::$server->getUserManager();
$groupManager = OC::$server->getGroupManager();
$config = \OC::$server->getConfig();

$app = new Application();

/** @var Symfony\Component\Console\Application $application */
$application->add(new CreateCalendar($userManager, $groupManager, $dbConnection));
$application->add(new CreateAddressBook($userManager, $app->getContainer()->query('CardDavBackend')));
$application->add(new SyncSystemAddressBook($app->getSyncService()));

// the occ tool is *for now* only available in debug mode for developers to test
if ($config->getSystemValue('debug', false)){
	$app = new \OCA\Dav\AppInfo\Application();
	$migration = $app->getContainer()->query('MigrateAddressbooks');
	$application->add(new MigrateAddressbooks($userManager, $migration));
	$migration = $app->getContainer()->query('MigrateCalendars');
	$application->add(new MigrateCalendars($userManager, $migration));
}
