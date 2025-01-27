<?php

/**
 * ECPrivateKey
 *
 * From: https://tools.ietf.org/html/rfc5915
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
 * ECPrivateKey
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class ECPrivateKey {

	const MAP = array(
		'type'     => ASN1::TYPE_SEQUENCE,
		'children' => array(
			'version'    => array(
				'type'    => ASN1::TYPE_INTEGER,
				'mapping' => array( 1 => 'ecPrivkeyVer1' ),
			),
			'privateKey' => array( 'type' => ASN1::TYPE_OCTET_STRING ),
			'parameters' => array(
				'constant' => 0,
				'optional' => true,
				'explicit' => true,
			) + ECParameters::MAP,
			'publicKey'  => array(
				'type'     => ASN1::TYPE_BIT_STRING,
				'constant' => 1,
				'optional' => true,
				'explicit' => true,
			),
		),
	);
}
