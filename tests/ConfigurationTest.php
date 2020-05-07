<?php
use PHPUnit\Framework\TestCase;
use SCBot\Configuration\Configuration;

require_once 'configuration.php';

class ConfigurationTest extends TestCase
{
    public function testCanLoadFile() {
        $config = Configuration::loadFromFile('tests/test.conf');
        $this->assertSame('testDB', $config->dbName);
        $this->assertSame('testUser', $config->dbUser);
        $this->assertSame('testPassword', $config->dbPassword);
        $this->assertSame('testHost', $config->dbHost);
        $this->assertSame('utf-8', $config->dbCharset);
        $this->assertSame('', $config->dbCollate);
    }

}


?>
