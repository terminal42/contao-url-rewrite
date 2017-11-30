<?php

declare(strict_types = 1);

namespace Terminal42\UrlRewriteBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Terminal42\UrlRewriteBundle\DependencyInjection\Configuration;

class ConfigurationTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Configuration::class, new Configuration());
    }

    public function testValidConfig()
    {
        $yaml = Yaml::parse(file_get_contents(__DIR__ . '/../Fixtures/config_valid.yml'));
        $config = (new Processor())->processConfiguration(new Configuration(), $yaml);

        $expected = [
            'backend_management' => true,
            'entries' => [
                [
                    'request' => [
                        'path' => 'find/{address}',
                        'hosts' => [],
                        'requirements' => [],
                    ],
                    'response' => [
                        'code' => 303,
                        'uri' => 'https://www.google.com/maps?q={address}',
                    ],
                ],
                [
                    'request' => [
                        'path' => 'news/{news}',
                        'requirements' => ['news' => '\d+'],
                        'hosts' => [],
                    ],
                    'response' => [
                        'code' => 301,
                        'uri' => '{{news_url::{news}|absolute}}'
                    ],
                ],
                [
                    'request' => [
                        'path' => 'home.php',
                        'hosts' => ['localhost'],
                        'condition' => "context.getMethod() == 'GET' and request.query.has('page')",
                        'requirements' => [],
                    ],
                    'response' => [
                        'uri' => '{{link_url::{page}|absolute}}',
                        'code' => 301,
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $config);
    }

    public function testInvalidConfig()
    {
        $this->expectException(InvalidConfigurationException::class);

        $yaml = Yaml::parse(file_get_contents(__DIR__ . '/../Fixtures/config_invalid.yml'));
        (new Processor())->processConfiguration(new Configuration(), $yaml);
    }
}
