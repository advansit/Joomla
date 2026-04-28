<?php
namespace Advans\Plugin\Privacy\J2Commerce\Tests\Integration;

use PHPUnit\Framework\TestCase;

class InstallationTest extends TestCase
{
    private $packagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->packagePath = dirname(__DIR__, 2) . '/../packages/plg_privacy_j2commerce.zip';
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
        
        $manifestExists = $zip->locateName('j2commerce.xml') !== false;
        
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
            'src/Extension/J2Commerce.php',
            'language/en-GB/plg_privacy_j2commerce.ini',
            'language/de-DE/plg_privacy_j2commerce.ini',
        ];
        
        foreach ($requiredFiles as $file) {
            $this->assertNotFalse(
                $zip->locateName($file),
                "Required file not found in package: {$file}"
            );
        }
        
        $zip->close();
    }

    public function testTemplateOverrideFilesExistInPackage(): void
    {
        $zip = new \ZipArchive();
        $zip->open($this->packagePath);

        $overrideFiles = [
            'overrides/com_j2store/checkout/default_shipping_payment.php',
            'overrides/com_j2store/myprofile/default.php',
            'overrides/com_j2store/myprofile/default_addresses.php',
        ];

        foreach ($overrideFiles as $file) {
            $this->assertNotFalse(
                $zip->locateName($file),
                "Template override not found in package: {$file}"
            );
        }

        $zip->close();
    }

    public function testTemplateOverridesContainPluginCheck(): void
    {
        $zip = new \ZipArchive();
        $zip->open($this->packagePath);

        // Both MyProfile overrides must check for the plugin via PluginHelper
        $filesToCheck = [
            'overrides/com_j2store/myprofile/default.php',
            'overrides/com_j2store/checkout/default_shipping_payment.php',
        ];

        foreach ($filesToCheck as $file) {
            $content = $zip->getFromName($file);
            $this->assertNotFalse($content, "Could not read {$file} from package");
            $this->assertStringContainsString(
                'PluginHelper',
                $content,
                "{$file} must use PluginHelper to check for the privacy plugin"
            );
        }

        $zip->close();
    }

    public function testManifestIsValidXml(): void
    {
        $zip = new \ZipArchive();
        $zip->open($this->packagePath);
        
        $manifestContent = $zip->getFromName('j2commerce.xml');
        $xml = simplexml_load_string($manifestContent);
        
        $this->assertNotFalse($xml, 'Manifest is not valid XML');
        $this->assertEquals('plugin', (string)$xml['type']);
        $this->assertEquals('privacy', (string)$xml['group']);
        
        $zip->close();
    }
}
