<?php

/**
 * DisplayText
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
 * DisplayText
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class DisplayText {

	const MAP = array(
		'type'     => ASN1::TYPE_CHOICE,
		'children' => array(
			'ia5String'     => array( 'type' => ASN1::TYPE_IA5_STRING ),
			'visibleString' => array( 'type' => ASN1::TYPE_VISIBLE_STRING ),
			'bmpString'     => array( 'type' => ASN1::TYPE_BMP_STRING ),
			'utf8String'    => array( 'type' => ASN1::TYPE_UTF8_STRING ),
		),
	);
}
