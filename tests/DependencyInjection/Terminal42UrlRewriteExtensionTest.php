<?php

declare(strict_types = 1);

namespace Terminal42\UrlRewriteBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Terminal42\UrlRewriteBundle\DependencyInjection\Terminal42UrlRewriteExtension;

class Terminal42UrlRewriteExtensionTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Terminal42UrlRewriteExtension::class, new Terminal42UrlRewriteExtension());
    }

    public function testLoad()
    {
        $container = new ContainerBuilder();
        $extension = new Terminal42UrlRewriteExtension();
        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('terminal42_url_rewrite.listener.rewrite_container'));
        $this->assertTrue($container->hasDefinition('terminal42_url_rewrite.rewrite_controller'));
    }
}
