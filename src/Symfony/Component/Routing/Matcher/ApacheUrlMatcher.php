<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Matcher;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * ApacheUrlMatcher matches URL based on Apache mod_rewrite matching (see ApacheMatcherDumper).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ApacheUrlMatcher extends UrlMatcher
{
    /**
     * Tries to match a URL based on Apache mod_rewrite matching.
     *
     * Returns false if no route matches the URL.
     *
     * @param string $pathinfo The pathinfo to be parsed
     *
     * @return array An array of parameters
     *
     * @throws MethodNotAllowedException If the current method is not allowed
     */
    public function match($pathinfo)
    {
        $parameters = array();
        $allow = array();
        $match = false;

        foreach ($_SERVER as $key => $value) {
            $name = $key;

            while (0 === strpos($name, 'REDIRECT_')) {
                $name = substr($name, 9);
            }

            // expects _ROUTING_<type>_<name>
            // or _ROUTING_<type>
            if (0 === strpos($name, '_ROUTING_')) {
                if (false !== $pos = strpos($name, '_', 9)) {
                    $type = substr($name, 9, $pos-9);
                    $name = substr($name, $pos+1);
                } else {
                    $type = substr($name, 9);
                }
            } else {
                continue;
            }

            if ('route' === $type) {
                $match = true;
                $parameters['_route'] = $value;
            } else if ('allow' === $type) {
                $allow[] = $name;
            } else if ('param' === $type) {
                $parameters[$name] = $value;
            } else if ('default' === $type) {
                if (!isset($parameters[$name])) {
                    $parameters[$name] = $value;
                }
            }

            unset($_SERVER[$key]);
        }

        if ($match) {
            return $parameters;
        } elseif (0 < count($allow)) {
            throw new MethodNotAllowedException($allow);
        } else {
            return parent::match($pathinfo);
        }
    }
}
