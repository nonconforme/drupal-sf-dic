<?php

namespace MakinaCorpus\Drupal\Sf\Container\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TwigCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('twig')) {
            return;
        }

        if (!$container->hasDefinition('templating')) {
            // When in fullstack, the definition exists and may chain more
            // than one templating engine, we will overrde this only in last
            // resort if full stack is not here
            $container->addDefinitions([
                'templating' => (new Definition())
                    ->setClass('Symfony\Bundle\TwigBundle\TwigEngine')
                    ->setArguments([
                        new Reference('twig'),
                        new Reference('templating.name_parser'),
                        new Reference('templating.locator'),
                    ])
            ]);
        }

        if (class_exists('\TFD_Environment')) {
            // This is very specific to be used with the TFD7 Drupal theme
            // engine, we do provide an alternate twig engine but using this
            // will make your project compatible with already written theme,
            // so let's override the twig environment using the custom one
            $twigEnvDefinition = $container->getDefinition('twig');
            $twigEnvDefinition->setClass('MakinaCorpus\Drupal\Sf\Twig\TFD\Environment');
            if (class_exists('\TFD_Extension')) {
                $twigEnvDefinition->addMethodCall('addExtension', [new Definition('TFD_Extension')]);
            }
        }

        // Do not override TwigBundle and FrameworkBundle services
        if (!$container->hasDefinition('templating.locator')) {
            $container->addDefinitions([
                'templating.locator' => (new Definition())
                    ->setClass('MakinaCorpus\Drupal\Sf\Templating\Loader\TemplateLocator')
                    ->setArguments([
                        new Reference('kernel'),
                        $container->getParameter('kernel.cache_dir'),
                    ])
            ]);
        }

        // Do not override TwigBundle and FrameworkBundle services
        if (!$container->hasDefinition('templating.name_parser')) {
            $container->addDefinitions([
                'templating.name_parser.legacy' => (new Definition())
                    ->setClass('MakinaCorpus\Drupal\Sf\Templating\TemplateNameParser')
                    ->setArguments([new Reference('kernel')]),
                'templating.name_parser' => (new Definition())
                    ->setClass('MakinaCorpus\Drupal\Sf\Templating\DrupalTemplateNameParser')
                    ->setArguments([
                        new Reference('kernel'),
                        new Reference('templating.name_parser.legacy'),
                    ]),
            ]);
        } else {
            $definition = $container->getDefinition('templating.name_parser');
            $container->addDefinitions([
                'templating.name_parser.legacy' => $definition,
                'templating.name_parser' => (new Definition())
                    ->setClass('MakinaCorpus\Drupal\Sf\Templating\DrupalTemplateNameParser')
                    ->setArguments([
                        new Reference('kernel'),
                        new Reference('templating.name_parser.legacy'),
                    ]),
            ]);
        }
    }
}
