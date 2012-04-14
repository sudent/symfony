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

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Container for a Route
 *
 * @author Arnaud Le Blanc <arnaud.lb@gmail.com>
 */
class DumperRoute
{
    private $name;
    private $route;
    private $parentCollection;

    /**
     * @param string          $name             The route name
     * @param Route           $route            The route
     * @param RouteCollection $parentCollection The parent of the route
     */
    public function __construct($name, Route $route, RouteCollection $parentCollection)
    {
        $this->name = $name;
        $this->route = $route;
        $this->parentCollection = $parentCollection;
    }

    /**
     * Returns the route name
     *
     * @return string The route name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the route
     *
     * @return Route The route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Returns the parent collection
     *
     * @return RouteCollection the parent collection
     */
    public function getParentCollection()
    {
        return $this->parentCollection;
    }
}
