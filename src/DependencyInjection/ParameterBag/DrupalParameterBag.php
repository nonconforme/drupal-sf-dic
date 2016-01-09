<?php

namespace MakinaCorpus\Drupal\Sf\Container\DependencyInjection\ParameterBag;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DrupalParameterBag extends ParameterBag
{
    /**
     * {@inheritDoc}
     */
    public function get($name)
    {
        if (array_key_exists($name, $GLOBALS['conf'])) {
            return $GLOBALS['conf'][$name];
        }

        return parent::get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function has($name)
    {
        return array_key_exists($name, $GLOBALS['conf']) || parent::has($name);
    }
}
