<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigProviderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * @var string
     */
    private $chain;

    /**
     * @var string
     */
    private $tag;

    /**
     * ConfigProviderPass constructor.
     *
     * @param string $chain
     * @param string $tag
     */
    public function __construct($chain, $tag)
    {
        $this->chain = $chain;
        $this->tag = $tag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has($this->chain)) {
            return;
        }

        $definition = $container->findDefinition($this->chain);

        foreach ($this->findAndSortTaggedServices($this->tag, $container) as $services) {
            foreach ($services as $service) {
                $definition->addMethodCall('addProvider', [$service]);
            }
        }
    }
}
