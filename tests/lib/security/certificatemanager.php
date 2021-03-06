<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use \OC\Security\CertificateManager;

class CertificateManagerTest extends \Test\TestCase {

	/** @var CertificateManager */
	private $certificateManager;
	/** @var String */
	private $username;

	protected function setUp() {
		parent::setUp();

		$this->username = $this->getUniqueID('', 20);
		OC_User::createUser($this->username, $this->getUniqueID('', 20));

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_Util::setupFS($this->username);

		$this->certificateManager = new CertificateManager($this->username, new \OC\Files\View());
	}

	protected function tearDown() {
		\OC_User::deleteUser($this->username);
		parent::tearDown();
	}

	protected function assertEqualsArrays($expected, $actual) {
		sort($expected);
		sort($actual);

		$this->assertEquals($expected, $actual);
	}

	function testListCertificates() {
		// Test empty certificate bundle
		$this->assertSame(array(), $this->certificateManager->listCertificates());

		// Add some certificates
		$this->certificateManager->addCertificate(file_get_contents(__DIR__.'/../../data/certificates/goodCertificate.crt'), 'GoodCertificate');
		$certificateStore = array();
		$certificateStore[] =  new \OC\Security\Certificate(file_get_contents(__DIR__.'/../../data/certificates/goodCertificate.crt'), 'GoodCertificate');
		$this->assertEqualsArrays($certificateStore, $this->certificateManager->listCertificates());

		// Add another certificates
		$this->certificateManager->addCertificate(file_get_contents(__DIR__.'/../../data/certificates/expiredCertificate.crt'), 'ExpiredCertificate');
		$certificateStore[] =  new \OC\Security\Certificate(file_get_contents(__DIR__.'/../../data/certificates/expiredCertificate.crt'), 'ExpiredCertificate');
		$this->assertEqualsArrays($certificateStore, $this->certificateManager->listCertificates());
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Certificate could not get parsed.
	 */
	function testAddInvalidCertificate() {
		$this->certificateManager->addCertificate('InvalidCertificate', 'invalidCertificate');
	}

	/**
	 * @return array
	 */
	public function dangerousFileProvider() {
		return [
			['.htaccess'],
			['../../foo.txt'],
			['..\..\foo.txt'],
		];
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Filename is not valid
	 * @dataProvider dangerousFileProvider
	 * @param string $filename
	 */
	function testAddDangerousFile($filename) {
		$this->certificateManager->addCertificate(file_get_contents(__DIR__.'/../../data/certificates/expiredCertificate.crt'), $filename);
	}

	function testRemoveDangerousFile() {
		$this->assertFalse($this->certificateManager->removeCertificate('../../foo.txt'));
	}

	function testRemoveExistingFile() {
		$this->certificateManager->addCertificate(file_get_contents(__DIR__.'/../../data/certificates/goodCertificate.crt'), 'GoodCertificate');
		$this->assertTrue($this->certificateManager->removeCertificate('GoodCertificate'));
	}

	function testGetCertificateBundle() {
		$this->assertSame('/' . $this->username . '/files_external/rootcerts.crt', $this->certificateManager->getCertificateBundle());
	}

}
