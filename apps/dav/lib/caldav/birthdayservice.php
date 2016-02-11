<?php
/**
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

namespace OCA\DAV\CalDAV;

use Exception;
use OCA\DAV\CardDAV\CardDavBackend;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class BirthdayService {
	/**
	 * BirthdayService constructor.
	 *
	 * @param CalDavBackend $calDavBackEnd
	 * @param CardDavBackend $cardDavBackEnd
	 */
	public function __construct($calDavBackEnd, $cardDavBackEnd) {
		$this->calDavBackEnd = $calDavBackEnd;
		$this->cardDavBackEnd = $cardDavBackEnd;
	}

	/**
	 * @param int $addressBookId
	 * @param string $cardUri
	 * @param string $cardData
	 */
	public function onCardChanged($addressBookId, $cardUri, $cardData) {

		$book = $this->cardDavBackEnd->getAddressBookById($addressBookId);
		$principalUri = $book['principaluri'];
		$calendarUri = 'contact_birthdays';
		$calendar = $this->ensureCalendarExists($principalUri, $calendarUri, []);
		$objectUri = $book['uri'] . '-' . $cardUri. '.ics';
		$calendarData = $this->buildBirthdayFromContact($cardData);
		if (is_null($calendarData)) {
			$this->calDavBackEnd->deleteCalendarObject($calendar['id'], $objectUri);
		} else {
			$existing = $this->calDavBackEnd->getCalendarObject($calendar['id'], $objectUri);
			if (is_null($existing)) {
				$this->calDavBackEnd->createCalendarObject($calendar['id'], $objectUri, $calendarData->serialize());
			} else {
				$this->calDavBackEnd->updateCalendarObject($calendar['id'], $objectUri, $calendarData->serialize());
			}
		}
	}

	/**
	 * @param int $addressBookId
	 * @param string $cardUri
	 */
	public function onCardDeleted($addressBookId, $cardUri) {
		$book = $this->cardDavBackEnd->getAddressBookById($addressBookId);
		$principalUri = $book['principaluri'];
		$calendarUri = 'contact_birthdays';
		$calendarId = $this->ensureCalendarExists($principalUri, $calendarUri, []);
		$objectUri = $book['uri'] . '-' . $cardUri. '.ics';
		$this->calDavBackEnd->deleteCalendarObject($calendarId, $objectUri);
	}

	/**
	 * @param string $principal
	 * @param string $id
	 * @param array $properties
	 * @return array|null
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function ensureCalendarExists($principal, $id, $properties) {
		$book = $this->calDavBackEnd->getCalendarByUri($principal, $id);
		if (!is_null($book)) {
			return $book;
		}
		$this->calDavBackEnd->createCalendar($principal, $id, $properties);

		return $this->calDavBackEnd->getCalendarByUri($principal, $id);
	}

	/**
	 * @param string $cardData
	 * @return null|VCalendar
	 */
	public function buildBirthdayFromContact($cardData) {
		if (empty($cardData)) {
			return null;
		}
		try {
			$doc = Reader::read($cardData);
		} catch (Exception $e) {
			return null;
		}

		if (!isset($doc->BDAY)) {
			return null;
		}
		$birthday = $doc->BDAY;
		if (!(string)$birthday) {
			return null;
		}
		$title = str_replace('{name}',
			strtr((string)$doc->FN, array('\,' => ',', '\;' => ';')),
			'{name}\'s Birthday'
		);
		try {
			$date = new \DateTime($birthday);
		} catch (Exception $e) {
			return null;
		}
		$vCal = new VCalendar();
		$vCal->VERSION = '2.0';
		$vEvent = $vCal->createComponent('VEVENT');
		$vEvent->add('DTSTART');
		$vEvent->DTSTART->setDateTime(
			$date
		);
		$vEvent->DTSTART['VALUE'] = 'DATE';
		$vEvent->add('DTEND');
		$date->add(new \DateInterval('P1D'));
		$vEvent->DTEND->setDateTime(
			$date
		);
		$vEvent->DTEND['VALUE'] = 'DATE';
//		$lm = new \DateTime('@' . $this->lastModified());
//		$lm->setTimeZone(new \DateTimeZone('UTC'));
//		$vEvent->DTSTAMP->setDateTime($lm);
		$vEvent->{'UID'} = $doc->UID;
		$vEvent->{'RRULE'} = 'FREQ=YEARLY';
		$vEvent->{'SUMMARY'} = $title . ' (' . $date->format('Y') . ')';
		$vEvent->{'TRANSP'} = 'TRANSPARENT';
//		$appInfo = \OCP\App::getAppInfo('contacts');
//		$appVersion = \OCP\App::getAppVersion('contacts');
//		$vCal->PRODID = '-//ownCloud//NONSGML ' . $appInfo['name'] . ' ' . $appVersion . '//EN';
		$vCal->add($vEvent);
		return $vCal;
	}

}
