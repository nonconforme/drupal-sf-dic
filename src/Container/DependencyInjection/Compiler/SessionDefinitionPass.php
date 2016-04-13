<?php

namespace MakinaCorpus\Drupal\Sf\Container\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SessionDefinitionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('session')) {
            // This is a very ugly workaround, but it allows us to provide
            // an almost compatible session definition whenever we're not in
            // the fullstack context, and allows us to use more Drupal 8
            // compatible code
            $container->addDefinitions(['session' => (new Definition())
                ->setClass('MakinaCorpus\Drupal\Sf\Session\DrupalSession')
            ]);
        }
    }
}
