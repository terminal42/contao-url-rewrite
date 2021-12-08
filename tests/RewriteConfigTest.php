<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\Tests;

use PHPUnit\Framework\TestCase;
use Terminal42\UrlRewriteBundle\RewriteConfig;

class RewriteConfigTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $config = new RewriteConfig('1', 'foo/bar');
        $config->setRequestHosts(['domain1.tld', 'domain1.tld', 'domain2.tld', '']);
        $config->setRequestRequirements(['foo' => '\d+', 'bar' => '\s+']);
        $config->setRequestCondition("request.query.has('foobar')");
        $config->setResponseCode(303);
        $config->setResponseUri('foo/baz');

        $this->assertSame('1', $config->getIdentifier());
        $this->assertSame('foo/bar', $config->getRequestPath());
        $this->assertSame(['domain1.tld', 'domain2.tld'], $config->getRequestHosts());
        $this->assertSame(['foo' => '\d+', 'bar' => '\s+'], $config->getRequestRequirements());
        $this->assertSame("request.query.has('foobar')", $config->getRequestCondition());
        $this->assertSame(303, $config->getResponseCode());
        $this->assertSame('foo/baz', $config->getResponseUri());

        $config->setIdentifier('2');
        $config->setRequestPath('foo/baz');

        $this->assertSame('2', $config->getIdentifier());
        $this->assertSame('foo/baz', $config->getRequestPath());
    }

    public function testInvalidResponseCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RewriteConfig('1', 'foobar', 500);
    }
}
