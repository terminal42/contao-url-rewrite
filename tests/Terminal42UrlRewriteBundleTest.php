<?php

declare(strict_types = 1);

namespace Terminal42\UrlRewriteBundle\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Terminal42\UrlRewriteBundle\Terminal42UrlRewriteBundle;

class Terminal42UrlRewriteBundleTest extends TestCase
{
    public function testInstantiation()
    {
        static::assertInstanceOf(Terminal42UrlRewriteBundle::class, new Terminal42UrlRewriteBundle());
    }

    public function testBuild()
    {
        $container = new ContainerBuilder();
        $bundle = new Terminal42UrlRewriteBundle();
        $bundle->build($container);

        static::assertEquals($container, new ContainerBuilder());
    }
}
