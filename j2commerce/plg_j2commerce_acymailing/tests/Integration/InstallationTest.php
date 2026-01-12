<?php
namespace Advans\Plugin\J2Commerce\AcyMailing\Tests\Integration;

use PHPUnit\Framework\TestCase;

class InstallationTest extends TestCase
{
    private $packagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->packagePath = dirname(__DIR__, 2) . '/../packages/plg_j2commerce_acymailing.zip';
    }

    public function testPackageExists(): void
    {
        $this->assertFileExists(
            $this->packagePath,
            'Package file does not exist. Run build.sh first.'
        );
    }

    public function testPackageIsValidZip(): void
    {
        $zip = new \ZipArchive();
        $result = $zip->open($this->packagePath);
        
        $this->assertTrue(
            $result === true,
            'Package is not a valid ZIP file'
        );
        
        $zip->close();
    }

    public function testManifestExistsInPackage(): void
    {
        $zip = new \ZipArchive();
        $zip->open($this->packagePath);
        
        $manifestExists = $zip->locateName('acymailing.xml') !== false;
        
        $this->assertTrue(
            $manifestExists,
            'Manifest file not found in package'
        );
        
        $zip->close();
    }

    public function testRequiredFilesExistInPackage(): void
    {
        $zip = new \ZipArchive();
        $zip->open($this->packagePath);
        
        $requiredFiles = [
            'services/provider.php',
            'src/Extension/AcyMailing.php',
            'tmpl/checkout.php',
            'language/en-GB/plg_j2commerce_acymailing.ini',
        ];
        
        foreach ($requiredFiles as $file) {
            $this->assertNotFalse(
                $zip->locateName($file),
                "Required file not found in package: {$file}"
            );
        }
        
        $zip->close();
    }

    public function testManifestIsValidXml(): void
    {
        $zip = new \ZipArchive();
        $zip->open($this->packagePath);
        
        $manifestContent = $zip->getFromName('acymailing.xml');
        $xml = simplexml_load_string($manifestContent);
        
        $this->assertNotFalse($xml, 'Manifest is not valid XML');
        $this->assertEquals('plugin', (string)$xml['type']);
        $this->assertEquals('j2commerce', (string)$xml['group']);
        
        $zip->close();
    }
}
