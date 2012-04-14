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
 * Collection of routes with attributes
 *
 * @author Arnaud Le Blanc <arnaud.lb@gmail.com>
 */
class DumperCollection implements \IteratorAggregate
{
    private $parent;
    private $routes;
    private $attributes;

    /**
     * @param  array   $routes     Array of DumperCollection|DumperRoute
     * @param  array   $atributes  Array of attributes
     */
    public function __construct(array $routes = array(), array $attributes = array())
    {
        $this->routes = $routes;
        $this->attributes = $attributes;
    }

    /**
     * Returns the parent collection
     *
     * @return DumperCollection The parent collection
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent collection
     *
     * @param DumperCollection $parent The parent collection
     */
    public function setParent(DumperCollection $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Returns the child routes
     *
     * @return array Array of DumperCollection|DumperRoute
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Gets a route by index
     *
     * @param  int $index                   The index
     * @return DumperCollection|DumperRoute The route at given index
     */
    public function getRoute($index)
    {
        return $this->routes[$index];
    }

    /**
     * Adds a route or route collection
     *
     * @param DumperCollection|DumperRoute The route or route collection
     */
    public function addRoute($route)
    {
        if ($route instanceof DumperCollection) {
            $route->setParent($this);
        }
        $this->routes[] = $route;
    }

    /**
     * Returns true if the attribute is defined
     *
     * @param  string  $name The attribute name
     * @return Boolean true if the attribute is defined, false otherwise
     */
    public function has($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Returns an attribute by name
     *
     * @param  string $name      The attribute name
     * @param  mixed  $default   Default value is the attribute doesn't exist
     * @return mixed  The attribute value
     */
    public function get($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->attributes[$name];
        } else {
            return $default;
        }
    }

    /**
     * Sets an attribute by name
     *
     * @param string $name  The attribute name
     * @param mixed  $value The attribute value
     */
    public function set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Returns an iterator over the children
     *
     * @return \Iterator The iterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }

    /**
     * Clones the collection and its parents, up to the given parent
     *
     * Children are reset
     *
     * @param  DumperCollection $until If given, cloning will stop before this parent
     * @return DumperCollection The cloned collection
     */
    public function cloneHierarchyUntil(DumperCollection $until = null)
    {
        $parent = null;

        if ($this->parent && $until !== $this->parent) {
            $parent = $this->parent->cloneHierarchyUntil($until);
        }

        $clone = new static(array(), $this->attributes);

        if ($parent) {
            $parent->addRoute($clone);
        }

        return $clone;
    }

    /**
     * Returns the root of the collection
     *
     * @return DumperCollection|null The root collection
     */
    public function getRoot()
    {
        if (null !== $parent = $this->parent) {
            return $parent->getRoot();
        } else {
            return $this;
        }
    }

    /**
     * Returns an array of parent collections, from the closer parent to the root
     *
     * @return array Array of DumperCollection parents
     */
    public function getParents()
    {
        if ($parent = $this->parent) {
            return array_merge(array($parent), $parent->getParents());
        } else {
            return array();
        }
    }

    /**
     * Returns an array of this collection and its parents, from this collection to the root
     *
     * @return array Array of DumperCollection collections
     */
    public function getParentsAndSelf()
    {
        return array_merge(array($this), $this->getParents());
    }

    /**
     * Returns a debug string representation of this collection
     *
     * @param Callable $toString Callback used to get the string representation of each individual child
     * @param string   $prefix   String prepended to each line
     */
    public function toString($toString, $prefix)
    {
        $string = '';
        foreach ($this->routes as $route) {
            $string .= sprintf("%s|-%s\n", $prefix, $toString($route));
            if ($route instanceof DumperCollection) {
                $string .= $route->toString($toString, $prefix.'| ');
            }
        }

        return $string;
    }
}

