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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigProviderPass implements CompilerPassInterface
{
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
        $providers = [];

        // Get the config providers in the relevant order by priority
        foreach ($container->findTaggedServiceIds($this->tag) as $id => $tags) {
            $priority = isset($tags[0]['priority']) ? $tags[0]['priority'] : 0;
            $providers[$priority][] = new Reference($id);
        }

        krsort($providers);

        // Add the providers to the service
        foreach ($providers as $v) {
            foreach ($v as $vv) {
                $definition->addMethodCall('addProvider', [$vv]);
            }
        }
    }
}
