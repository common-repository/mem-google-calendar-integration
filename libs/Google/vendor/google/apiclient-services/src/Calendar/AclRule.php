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

class AclRule extends \Google\Model {

	public $etag;
	public $id;
	public $kind;
	public $role;
	protected $scopeType     = AclRuleScope::class;
	protected $scopeDataType = '';

	public function setEtag( $etag ) {
		$this->etag = $etag;
	}
	public function getEtag() {
		 return $this->etag;
	}
	public function setId( $id ) {
		$this->id = $id;
	}
	public function getId() {
		return $this->id;
	}
	public function setKind( $kind ) {
		$this->kind = $kind;
	}
	public function getKind() {
		 return $this->kind;
	}
	public function setRole( $role ) {
		$this->role = $role;
	}
	public function getRole() {
		 return $this->role;
	}
	/**
	 * @param AclRuleScope
	 */
	public function setScope( AclRuleScope $scope ) {
		$this->scope = $scope;
	}
	/**
	 * @return AclRuleScope
	 */
	public function getScope() {
		return $this->scope;
	}
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias( AclRule::class, 'Google_Service_Calendar_AclRule' );
