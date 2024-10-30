<?php

/**
 * DHParameter
 *
 * From: https://www.teletrust.de/fileadmin/files/oid/oid_pkcs-3v1-4.pdf#page=6
 *
 * PHP version 5
 *
 * @category  File
 * @package   ASN1
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\File\ASN1\Maps;

use phpseclib3\File\ASN1;

/**
 * DHParameter
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class DHParameter {

	const MAP = array(
		'type'     => ASN1::TYPE_SEQUENCE,
		'children' => array(
			'prime'              => array( 'type' => ASN1::TYPE_INTEGER ),
			'base'               => array( 'type' => ASN1::TYPE_INTEGER ),
			'privateValueLength' => array(
				'type'     => ASN1::TYPE_INTEGER,
				'optional' => true,
			),
		),
	);
}
