<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Calendar;

class CalendarList extends \Google\Collection {

	protected $collection_key = 'items';
	public $etag;
	protected $itemsType     = CalendarListEntry::class;
	protected $itemsDataType = 'array';
	public $kind;
	public $nextPageToken;
	public $nextSyncToken;

	public function setEtag( $etag ) {
		$this->etag = $etag;
	}
	public function getEtag() {
		 return $this->etag;
	}
	/**
	 * @param CalendarListEntry[]
	 */
	public function setItems( $items ) {
		$this->items = $items;
	}
	/**
	 * @return CalendarListEntry[]
	 */
	public function getItems() {
		return $this->items;
	}
	public function setKind( $kind ) {
		$this->kind = $kind;
	}
	public function getKind() {
		 return $this->kind;
	}
	public function setNextPageToken( $nextPageToken ) {
		$this->nextPageToken = $nextPageToken;
	}
	public function getNextPageToken() {
		return $this->nextPageToken;
	}
	public function setNextSyncToken( $nextSyncToken ) {
		$this->nextSyncToken = $nextSyncToken;
	}
	public function getNextSyncToken() {
		return $this->nextSyncToken;
	}
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias( CalendarList::class, 'Google_Service_Calendar_CalendarList' );
