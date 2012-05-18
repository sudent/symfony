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
 * PhpMatcherDumper creates a PHP class able to match URLs for a given set of routes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 * @author Arnaud Le Blanc <arnaud.lb@gmail.com>
 */
class PhpMatcherDumper extends MatcherDumper
{
    /**
     * Dumps a set of routes to a PHP class.
     *
     * Available options:
     *
     *  * class:      The class name
     *  * base_class: The base class name
     *
     * @param  array  $options An array of options
     *
     * @return string A PHP class representing the matcher class
     */
    public function dump(array $options = array())
    {
        $options = array_merge(array(
            'class'      => 'ProjectUrlMatcher',
            'base_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
        ), $options);

        // trailing slash support is only enabled if we know how to redirect the user
        $interfaces = class_implements($options['base_class']);
        $supportsRedirections = isset($interfaces['Symfony\\Component\\Routing\\Matcher\\RedirectableUrlMatcherInterface']);

        return <<<EOF
<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * {$options['class']}
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class {$options['class']} extends {$options['base_class']}
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext \$context)
    {
        \$this->context = \$context;
    }

{$this->generateMatchMethod($supportsRedirections)}
}

EOF;
    }

    /**
     * Generates the code for the match method implementing UrlMatcherInterface.
     *
     * @param Boolean $supportsRedirections Whether redirections are supported by the base class
     *
     * @return string Match method as PHP code
     */
    private function generateMatchMethod($supportsRedirections)
    {
        $code = rtrim($this->compileRoutes($this->getRoutes(), $supportsRedirections), "\n");

        return <<<EOF
    public function match(\$pathinfo)
    {
        \$allow = array();
        \$pathinfo = rawurldecode(\$pathinfo);

$code

        throw 0 < count(\$allow) ? new MethodNotAllowedException(array_unique(\$allow)) : new ResourceNotFoundException();
    }
EOF;
    }

    /**
     * Counts the number of routes as direct child of the RouteCollection.
     *
     * @param RouteCollection $routes A RouteCollection instance
     *
     * @return integer Number of Routes
     */
    private function countDirectChildRoutes(RouteCollection $routes)
    {
        $count = 0;
        foreach ($routes as $route) {
            if ($route instanceof Route) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generates PHP code recursively to match a RouteCollection with all child routes and child collections.
     *
     * @param RouteCollection $routes               A RouteCollection instance
     * @param Boolean         $supportsRedirections Whether redirections are supported by the base class
     * @param string|null     $parentPrefix         The prefix of the parent collection used to optimize the code
     *
     * @return string PHP code
     */
    private function compileRoutes(RouteCollection $routes, $supportsRedirections)
    {
        $code = '';
        $collections = $this->groupRoutesByHostnameRegex($routes)->getRoot();
        $fetchedHostname = false;

        foreach ($collections as $collection) {
            $indent = 0;

            if ($regex = $collection->get('hostname_regex')) {
                if (!$fetchedHostname) {
                    $code .= "        \$hostname = \$this->context->getHost();\n\n";
                    $fetchedHostname = true;
                }

                $code .= sprintf("        if (preg_match(%s, \$hostname, \$hostnameMatches)) {\n", var_export($regex, true));

                $indent = 4;
            }

            $collection = $this->buildPrefixTree($collection);
            $lines = $this->compilePrefixRoutes($collection, $supportsRedirections);
            $code .= $this->indentCode($lines, $indent);

            if ($regex) {
                $code .= "        }\n\n";
            }
        }

        return $code;
    }

    private function compilePrefixRoutes(DumperPrefixCollection $collection, $supportsRedirections, $parentPrefix = '')
    {
        $code = '';
        $indent = 0;
        $prefix = $collection->getPrefix();
        $optimizable = 1 < strlen($prefix) && 1 < count($collection->getRoutes());
        $optimizedPrefix = $parentPrefix;

        if ($optimizable) {
            $optimizedPrefix = $prefix;

            $code .= sprintf("        if (0 === strpos(\$pathinfo, %s)) {\n", var_export($prefix, true));
            $indent = 4;
        }

        foreach ($collection as $route) {
            if ($route instanceof DumperCollection) {
                $lines = $this->compilePrefixRoutes($route, $supportsRedirections, $optimizedPrefix);
            } else {
                $lines = $this->compileRoute($route->getRoute(), $route->getName(), $supportsRedirections, $optimizedPrefix);
            }
            $code .= $this->indentCode($lines, $indent);
        }

        if ($optimizable) {
            $code .= "        }\n\n";
        }

        return $code;
    }

    /**
     * Compiles a single Route to PHP code used to match it against the path info.
     *
     * @param Route       $routes               A Route instance
     * @param string      $name                 The name of the Route
     * @param Boolean     $supportsRedirections Whether redirections are supported by the base class
     * @param string|null $parentPrefix         The prefix of the parent collection used to optimize the code
     *
     * @return string PHP code
     */
    private function compileRoute(Route $route, $name, $supportsRedirections, $parentPrefix = null)
    {
        $code = '';
        $compiledRoute = $route->compile();
        $conditions = array();
        $hasTrailingSlash = false;
        $matches = false;
        $hostnameMatches = false;
        $methods = array();

        if ($req = $route->getRequirement('_method')) {
            $methods = explode('|', strtoupper($req));
            // GET and HEAD are equivalent
            if (in_array('GET', $methods) && !in_array('HEAD', $methods)) {
                $methods[] = 'HEAD';
            }
        }

        $supportsTrailingSlash = $supportsRedirections && (!$methods || in_array('HEAD', $methods));

        if (!count($compiledRoute->getPathVariables()) && false !== preg_match('#^(.)\^(?<url>.*?)\$\1#', $compiledRoute->getRegex(), $m)) {
            if ($supportsTrailingSlash && substr($m['url'], -1) === '/') {
                $conditions[] = sprintf("rtrim(\$pathinfo, '/') === %s", var_export(rtrim(str_replace('\\', '', $m['url']), '/'), true));
                $hasTrailingSlash = true;
            } else {
                $conditions[] = sprintf("\$pathinfo === %s", var_export(str_replace('\\', '', $m['url']), true));
            }
        } else {
            if ($compiledRoute->getStaticPrefix() && $compiledRoute->getStaticPrefix() !== $parentPrefix) {
                $conditions[] = sprintf("0 === strpos(\$pathinfo, %s)", var_export($compiledRoute->getStaticPrefix(), true));
            }

            $regex = $compiledRoute->getRegex();
            if ($supportsTrailingSlash && $pos = strpos($regex, '/$')) {
                $regex = substr($regex, 0, $pos).'/?$'.substr($regex, $pos + 2);
                $hasTrailingSlash = true;
            }
            $conditions[] = sprintf("preg_match(%s, \$pathinfo, \$matches)", var_export($regex, true));

            $matches = true;
        }

        if ($compiledRoute->getHostnameVariables()) {
            $hostnameMatches = true;
        }

        $conditions = implode(' && ', $conditions);

        $code .= <<<EOF
        // $name
        if ($conditions) {

EOF;

        if ($methods) {
            $gotoname = 'not_'.preg_replace('/[^A-Za-z0-9_]/', '', $name);

            if (1 === count($methods)) {
                $code .= <<<EOF
            if (\$this->context->getMethod() != '$methods[0]') {
                \$allow[] = '$methods[0]';
                goto $gotoname;
            }

EOF;
            } else {
                $methods = implode("', '", $methods);
                $code .= <<<EOF
            if (!in_array(\$this->context->getMethod(), array('$methods'))) {
                \$allow = array_merge(\$allow, array('$methods'));
                goto $gotoname;
            }

EOF;
            }
        }

        if ($hasTrailingSlash) {
            $code .= <<<EOF
            if (substr(\$pathinfo, -1) !== '/') {
                return \$this->redirect(\$pathinfo.'/', '$name');
            }

EOF;
        }

        if ($scheme = $route->getRequirement('_scheme')) {
            if (!$supportsRedirections) {
                throw new \LogicException('The "_scheme" requirement is only supported for URL matchers that implement RedirectableUrlMatcherInterface.');
            }

            $code .= <<<EOF
            if (\$this->context->getScheme() !== '$scheme') {
                return \$this->redirect(\$pathinfo, '$name', '$scheme');
            }

EOF;
        }

        // optimize parameters array
        if (($matches || $hostnameMatches) && $compiledRoute->getDefaults()) {
            $vars = array();
            if ($matches) {
                $vars[] = '$matches';
            }
            if ($hostnameMatches) {
                $vars[] = '$hostnameMatches';
            }
            $matchesExpr = implode(' + ', $vars);

            $code .= sprintf("            return array_merge(\$this->mergeDefaults(%s, %s), array('_route' => '%s'));\n"
                , $matchesExpr, str_replace("\n", '', var_export($compiledRoute->getDefaults(), true)), $name);

        } elseif ($matches || $hostnameMatches) {

            if (!$matches) {
                $code .= "            \$matches = \$hostnameMatches;\n";
            } else {
                if ($hostnameMatches) {
                    $code .= "            \$matches = \$matches + \$hostnameMatches;\n";
                }
            }

            $code .= sprintf("            \$matches['_route'] = '%s';\n", $name);
            $code .= "            return \$matches;\n";
        } elseif ($compiledRoute->getDefaults()) {
            $code .= sprintf("            return %s;\n", str_replace("\n", '', var_export(array_merge($compiledRoute->getDefaults(), array('_route' => $name)), true)));
        } else {
            $code .= sprintf("            return array('_route' => '%s');\n", $name);
        }
        $code .= "        }\n";

        if ($methods) {
            $code .= "        $gotoname:\n";
        }

        $code .= "\n";

        return $code;
    }

    /**
     * Prepends the given number of spaces at the begining of each line.
     *
     * @param  string $lines Lines of code
     * @param  int    $width The number of spaces
     *
     * @return string Indented lines
     */
    private function indentCode($lines, $width)
    {
        return preg_replace('#^(?=.)#m', str_repeat(' ', $width), $lines);
    }

    /**
     * Groups consecutive routes having the same hostname regex.
     *
     * The results is a collection of collections of routes having the same hostname regex.
     */
    private function groupRoutesByHostnameRegex(RouteCollection $routes, DumperCollection $root = null, DumperCollection $collection = null)
    {
        if (null === $root) {
            $root = new DumperCollection();
        }

        if (null === $collection) {
            $collection = new DumperCollection();
            $collection->set('hostname_regex', null);

            $root->addRoute($collection);
        }

        foreach ($routes as $name => $route) {
            if ($route instanceof RouteCollection) {
                $collection = $this->groupRoutesByHostnameRegex($route, $root, $collection);
            } else {
                $regex = $route->compile()->getHostnameRegex();

                if ($regex !== $collection->get('hostname_regex')) {
                    $collection = new DumperCollection();
                    $collection->set('hostname_regex', $regex);
                    $root->addRoute($collection);
                }

                $collection->addRoute(new DumperRoute($name, $route, $routes));
            }
        }

        return $collection;
    }

    /**
     * Organizes the routes into a prefix tree.
     *
     * Routes order is preserved such that traversing the tree will traverse the
     * routes in the origin order
     */
    private function buildPrefixTree(DumperCollection $collection)
    {
        $tree = new DumperPrefixCollection();
        $tree->setPrefix('');
        $current = $tree;

        foreach ($collection->getRoutes() as $route) {
            $current = $current->addPrefixRoute($route);
        }

        return $tree;
    }
}
