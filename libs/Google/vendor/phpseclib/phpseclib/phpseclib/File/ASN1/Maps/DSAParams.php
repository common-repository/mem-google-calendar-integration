<?php

/**
 * DSAParams
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
 * DSAParams
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class DSAParams {

	const MAP = array(
		'type'     => ASN1::TYPE_SEQUENCE,
		'children' => array(
			'p' => array( 'type' => ASN1::TYPE_INTEGER ),
			'q' => array( 'type' => ASN1::TYPE_INTEGER ),
			'g' => array( 'type' => ASN1::TYPE_INTEGER ),
		),
	);
}
