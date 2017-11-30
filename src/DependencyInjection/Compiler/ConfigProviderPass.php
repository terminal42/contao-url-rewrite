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

class ConfigProviderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * @var string
     */
    private $alias;

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
     * @param string $alias
     * @param string $chain
     * @param        $tag
     */
    public function __construct($alias, $chain, $tag)
    {
        $this->alias = $alias;
        $this->chain = $chain;
        $this->tag = $tag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = $this->findAndSortTaggedServices($this->tag, $container);

        // If there's only one service or chain service is not present alias the first service
        if ((count($services) === 1 && count($services[0]) === 1) || !$container->hasDefinition($this->chain)) {
            $container->setAlias($this->alias, (string) $services[0]);

            return;
        }

        $definition = $container->findDefinition($this->chain);

        // Add providers to the chain
        foreach ($services as $service) {
            $definition->addMethodCall('addProvider', [$service]);
        }
    }
}
