<?php

namespace Drupal\Module\sf_dic;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Drupal\Sf\Container\DependencyInjection\Compiler\AddConsoleCommandPass;
use MakinaCorpus\Drupal\Sf\Container\DependencyInjection\Compiler\ParametersToVariablesPass;
use MakinaCorpus\Drupal\Sf\Container\DependencyInjection\Compiler\SessionDefinitionPass;
use MakinaCorpus\Drupal\Sf\Container\DependencyInjection\Compiler\TwigCompilerPass;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        // Register ourself as a bundle would do
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));

        $container->addCompilerPass(new ParametersToVariablesPass());

        // @todo find a better way to determine if we are full
        // stack or not, this is someway kind of wrong
        $isFullstack = class_exists('MakinaCorpus\DrupalBundle\DrupalBundle');

        if (!$isFullstack) {

            $container->addCompilerPass(new RegisterListenersPass(
                'event_dispatcher', 'event_listener', 'event_subscriber'
            ));

            if (class_exists('Symfony\\Component\\Console\\Command\\Command')) {
                $container->addCompilerPass(new AddConsoleCommandPass());
            }

            // Avoid Symfony session, if set, to be overriden by ours
            $container->addCompilerPass(new SessionDefinitionPass());
        }

        // TwigBundle will automatically be registered in the kernel, even if
        // we are not in a fullstack context, it'll work gracefully and allow
        // use to fully use it
        if (class_exists('\Symfony\Bundle\TwigBundle\TwigBundle')) {
            $loader->load('templating.yml');
            $container->addCompilerPass(new TwigCompilerPass());
        }
    }
}
