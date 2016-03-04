<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MakinaCorpus\Drupal\Sf\Templating;

use Symfony\Component\Templating\TemplateNameParser as BaseTemplateNameParser;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Catches anything that looks like drupal theme hook names
 */
class DrupalTemplateNameParser extends BaseTemplateNameParser
{
    protected $cache = [];

    /**
     * @var TemplateNameParserInterface
     */
    protected $fallback;

    /**
     * Constructor.
     *
     * @param TemplateNameParserInterface $parent
     */
    public function __construct(TemplateNameParserInterface $fallback = null)
    {
        $this->fallback = $fallback;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($name)
    {
        if ($name instanceof TemplateReferenceInterface) {
            return $name;
        } else if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        // normalize name
        $name = str_replace(':/', ':', preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name)));

        $matches = [];

        // Little bit of explaination for this nice regex, first of, we cannot
        // check for "starting by" (^ operator) because Drupal theme() function
        // will prepend our identifiers by the file path, we must just drop it
        // silently if it's there. Then, we must absolutely ensure the template
        // name ends up with '.html.twig'. Finally, type:name:path are all
        // mandatory items else we cannot find the template real path.
        if (!preg_match('@([^/]+)\:([\w_\-]+)\:([^\:]+)\.([^\.]+)\.([^\.]+)$@', $name, $matches)) {
            if ($this->fallback) {
                return $this->fallback->parse($name);
            } else {
                return parent::parse($name);
            }

            throw new \InvalidArgumentException(); // Let it be found by fallback
        }

        $template = new DrupalTemplateReference($matches[1], $matches[2], $matches[3], $matches[4], $matches[5]);

        return $this->cache[$name] = $template;
    }
}