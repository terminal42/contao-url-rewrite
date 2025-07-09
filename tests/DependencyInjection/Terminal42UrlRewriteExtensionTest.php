<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Terminal42\UrlRewriteBundle\DependencyInjection\Terminal42UrlRewriteExtension;

class Terminal42UrlRewriteExtensionTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(Terminal42UrlRewriteExtension::class, new Terminal42UrlRewriteExtension());
    }

    public function testLoadWithBackendManagement(): void
    {
        $container = new ContainerBuilder();
        $extension = new Terminal42UrlRewriteExtension();
        $extension->load(['terminal42_url_rewrite' => ['backend_management' => true]], $container);

        $this->assertTrue($container->getParameter('terminal42_url_rewrite.backend_management'));
        $this->assertTrue($container->hasDefinition('terminal42_url_rewrite.provider.database'));

        $this->assertTrue($container->hasDefinition('terminal42_url_rewrite.listener.rewrite_container'));
        $this->assertTrue($container->hasDefinition('terminal42_url_rewrite.rewrite_controller'));
    }

    public function testLoadWithoutBackendManagement(): void
    {
        $container = new ContainerBuilder();
        $extension = new Terminal42UrlRewriteExtension();
        $extension->load(['terminal42_url_rewrite' => ['backend_management' => false]], $container);

        $this->assertFalse($container->getParameter('terminal42_url_rewrite.backend_management'));
        $this->assertFalse($container->hasDefinition('terminal42_url_rewrite.provider.database'));

        $this->assertTrue($container->hasDefinition('terminal42_url_rewrite.listener.rewrite_container'));
        $this->assertTrue($container->hasDefinition('terminal42_url_rewrite.rewrite_controller'));
    }
}
