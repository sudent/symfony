<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Matcher\Dumper;

/**
 * Prefix tree of routes preserving routes order
 *
 * @author Arnaud Le Blanc <arnaud.lb@gmail.com>
 */
class DumperPrefixCollection extends DumperCollection
{
    private $prefix;

    /**
     * Returns the prefix
     *
     * @return string The prefix
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Sets the prefix
     *
     * @param string $prefix The prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Adds a route in the tree
     *
     * @param  DumperRoute $route The route
     * @return DumperPrefixCollection The node the route was added to
     */
    public function addPrefixRoute(DumperRoute $route)
    {
        $prefix = $route->getRoute()->compile()->getStaticPrefix();

        if ($this->getPrefix() === $prefix) {
            $this->addRoute($route);
            return $this;

        } else if ('' === $this->getPrefix() || 0 === strpos($prefix, $this->getPrefix())) {
            $prev = $this;
            for ($i = strlen($this->getPrefix()); $i < strlen($prefix); ++$i) {
                $collection = new DumperPrefixCollection();
                $collection->setPrefix(substr($prefix, 0, $i+1));
                $prev->addRoute($collection);
                $prev = $collection;
            }

            $collection->addRoute($route);

            return $collection;

        } else {
            return $this->getParent()->addPrefixRoute($route);
        }
    }
}

