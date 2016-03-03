<?php

namespace MakinaCorpus\Drupal\Sf\Twig\Extension;

/**
 * Drupal image style display
 */
class DrupalImageExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('image', [$this, 'renderImage'], ['is_safe' => ['html']]),
        ];
    }

    public function renderImage($image, $style = null, $attributes = [])
    {
        if (empty($image)) {
            return '';
        }

        $uri      = null;
        $options  = [];

        if (is_scalar($image)) {
            $uri = (string)$image;
        } else {
            if (is_object($image)) {
                $image = (array)$image;
            }
            $uri = $image['uri'];
            foreach (['width', 'height', 'alt', 'title'] as $property) {
                if (isset($image[$property]) && empty($options[$property])) {
                    $options[$property] = $image[$property];
                }
            }
        }

        if ($style) {
            $hook = 'image_style';
            $options['style_name'] = $style;
        } else {
            $hook = 'image';
        }

        return theme($hook, ['path' => $uri, 'attributes'  => $attributes] + $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'drupal_image';
    }
}
