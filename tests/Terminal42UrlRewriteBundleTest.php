<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Terminal42\UrlRewriteBundle\DependencyInjection\Compiler\ConfigProviderPass;
use Terminal42\UrlRewriteBundle\Terminal42UrlRewriteBundle;

class Terminal42UrlRewriteBundleTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(Terminal42UrlRewriteBundle::class, new Terminal42UrlRewriteBundle());
    }

    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $bundle = new Terminal42UrlRewriteBundle();
        $bundle->build($container);

        $found = false;

        foreach ($container->getCompiler()->getPassConfig()->getPasses() as $pass) {
            if ($pass instanceof ConfigProviderPass) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }
}
