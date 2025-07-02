<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\ConfigProvider;

use PHPUnit\Framework\TestCase;
use Terminal42\UrlRewriteBundle\ConfigProvider\BundleConfigProvider;
use Terminal42\UrlRewriteBundle\RewriteConfig;

class BundleConfigProviderTest extends TestCase
{
    /**
     * @var BundleConfigProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new BundleConfigProvider([
            // Invalid entry
            ['request' => [], 'response' => []],

            // Valid entry
            [
                'request' => [
                    'path' => 'foo/bar',
                    'hosts' => ['domain1.tld', 'domain2.tld'],
                    'condition' => "request.query.has('foobar')",
                    'requirements' => ['foo' => '\d+', 'bar' => '\s+'],
                ],
                'response' => [
                    'code' => 303,
                    'uri' => 'foo/baz',
                ],
            ],
        ]);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(BundleConfigProvider::class, $this->provider);
    }

    public function testFind(): void
    {
        $config = $this->provider->find('1');

        $this->assertNull($this->provider->find('123'));

        $this->assertInstanceOf(RewriteConfig::class, $config);
        $this->assertSame('1', $config->getIdentifier());
        $this->assertSame('foo/bar', $config->getRequestPath());
        $this->assertSame(['domain1.tld', 'domain2.tld'], $config->getRequestHosts());
        $this->assertSame(['foo' => '\d+', 'bar' => '\s+'], $config->getRequestRequirements());
        $this->assertSame("request.query.has('foobar')", $config->getRequestCondition());
        $this->assertSame(303, $config->getResponseCode());
        $this->assertSame('foo/baz', $config->getResponseUri());
    }

    public function testFindAll(): void
    {
        $this->assertCount(0, (new BundleConfigProvider())->findAll());
        $this->assertCount(1, $this->provider->findAll());
    }
}
