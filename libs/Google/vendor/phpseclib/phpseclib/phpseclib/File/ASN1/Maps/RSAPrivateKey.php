<?php

/**
 * RSAPrivateKey
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
 * RSAPrivateKey
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class RSAPrivateKey {

	// version must be multi if otherPrimeInfos present
	const MAP = array(
		'type'     => ASN1::TYPE_SEQUENCE,
		'children' => array(
			'version'         => array(
				'type'    => ASN1::TYPE_INTEGER,
				'mapping' => array( 'two-prime', 'multi' ),
			),
			'modulus'         => array( 'type' => ASN1::TYPE_INTEGER ), // n
			'publicExponent'  => array( 'type' => ASN1::TYPE_INTEGER ), // e
			'privateExponent' => array( 'type' => ASN1::TYPE_INTEGER ), // d
			'prime1'          => array( 'type' => ASN1::TYPE_INTEGER ), // p
			'prime2'          => array( 'type' => ASN1::TYPE_INTEGER ), // q
			'exponent1'       => array( 'type' => ASN1::TYPE_INTEGER ), // d mod (p-1)
			'exponent2'       => array( 'type' => ASN1::TYPE_INTEGER ), // d mod (q-1)
			'coefficient'     => array( 'type' => ASN1::TYPE_INTEGER ), // (inverse of q) mod p
			'otherPrimeInfos' => OtherPrimeInfos::MAP + array( 'optional' => true ),
		),
	);
}
