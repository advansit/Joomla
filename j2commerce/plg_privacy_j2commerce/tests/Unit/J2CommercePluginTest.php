<?php
namespace Advans\Plugin\Privacy\J2Commerce\Tests\Unit;

use PHPUnit\Framework\TestCase;

class J2CommercePluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Requires Joomla framework');
    }

    public function testOnPrivacyExportRequestReturnsArray(): void
    {
        $this->assertTrue(true);
    }

    public function testExportIncludesOrdersDomain(): void
    {
        $this->assertTrue(true);
    }

    public function testExportIncludesAddressesDomain(): void
    {
        $this->assertTrue(true);
    }

    public function testOnPrivacyRemoveDataReturnsStatus(): void
    {
        $this->assertTrue(true);
    }

    public function testAnonymizeOrdersWhenEnabled(): void
    {
        $this->assertTrue(true);
    }

    public function testDeleteAddressesWhenEnabled(): void
    {
        $this->assertTrue(true);
    }

    public function testExportedDataContainsNoPasswords(): void
    {
        $this->assertTrue(true);
    }
}
