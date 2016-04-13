<?php

namespace MakinaCorpus\Drupal\Sf\Twig\Extension;

/**
 * Drupal various rendering functions
 */
class DrupalPathExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('path', [$this, 'createUrl'], ['is_safe' => ['html']]),
        ];
    }

    public function createUrl($route, array $parameters = [])
    {
        if ($parameters) {
            $tokens = [];
            foreach ($parameters as $key => $value) {
                if (false !== strpos($route, '%key')) {
                    $tokens['%' . $key] = $value;
                    unset($parameters[$key]);
                }
            }
            $route = strtr($route, $tokens);
        }

        return url(trim($route . '/' . implode('/', $parameters), '/'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'drupal_path';
    }
}
