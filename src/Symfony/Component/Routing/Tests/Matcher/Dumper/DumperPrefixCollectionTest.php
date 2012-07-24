<?php

namespace Symfony\Component\Routing\Tests\Matcher\Dumper;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\Dumper\DumperPrefixCollection;
use Symfony\Component\Routing\Matcher\Dumper\DumperRoute;

class DumperPrefixCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testAddPrefixRoute()
    {
        $coll = new DumperPrefixCollection;
        $coll->setPrefix('');

        $route = new DumperRoute('bar', new Route('/foo/bar'), new RouteCollection);
        $coll = $coll->addPrefixRoute($route);

        $route = new DumperRoute('bar2', new Route('/foo/bar'), new RouteCollection);
        $coll = $coll->addPrefixRoute($route);

        $route = new DumperRoute('qux', new Route('/foo/qux'), new RouteCollection);
        $coll = $coll->addPrefixRoute($route);

        $route = new DumperRoute('bar3', new Route('/foo/bar'), new RouteCollection);
        $result = $coll->addPrefixRoute($route);

        $expect = <<<'EOF'
            |-coll /
            | |-coll /f
            | | |-coll /fo
            | | | |-coll /foo
            | | | | |-coll /foo/
            | | | | | |-coll /foo/b
            | | | | | | |-coll /foo/ba
            | | | | | | | |-coll /foo/bar
            | | | | | | | | |-route bar /foo/bar
            | | | | | | | | |-route bar2 /foo/bar
            | | | | | |-coll /foo/q
            | | | | | | |-coll /foo/qu
            | | | | | | | |-coll /foo/qux
            | | | | | | | | |-route qux /foo/qux
            | | | | | |-coll /foo/b
            | | | | | | |-coll /foo/ba
            | | | | | | | |-coll /foo/bar
            | | | | | | | | |-route bar3 /foo/bar

EOF;

        $this->assertSame($expect, $result->getRoot()->toString(function($route) {
            if ($route instanceof DumperPrefixCollection) {
                return sprintf("coll %s", $route->getPrefix());
            } else {
                return sprintf("route %s %s", $route->getName(), $route->getRoute()->getPattern());
            }
        }, '            '));
    }

    public function testMergeSlashNodes()
    {
        $coll = new DumperPrefixCollection;
        $coll->setPrefix('');

        $route = new DumperRoute('bar', new Route('/foo/bar'), new RouteCollection);
        $coll = $coll->addPrefixRoute($route);

        $route = new DumperRoute('bar2', new Route('/foo/bar'), new RouteCollection);
        $coll = $coll->addPrefixRoute($route);

        $route = new DumperRoute('qux', new Route('/foo/qux'), new RouteCollection);
        $coll = $coll->addPrefixRoute($route);

        $route = new DumperRoute('bar3', new Route('/foo/bar'), new RouteCollection);
        $result = $coll->addPrefixRoute($route);

        $result->getRoot()->mergeSlashNodes();

        $expect = <<<'EOF'
            |-coll /f
            | |-coll /fo
            | | |-coll /foo
            | | | |-coll /foo/b
            | | | | |-coll /foo/ba
            | | | | | |-coll /foo/bar
            | | | | | | |-route bar /foo/bar
            | | | | | | |-route bar2 /foo/bar
            | | | |-coll /foo/q
            | | | | |-coll /foo/qu
            | | | | | |-coll /foo/qux
            | | | | | | |-route qux /foo/qux
            | | | |-coll /foo/b
            | | | | |-coll /foo/ba
            | | | | | |-coll /foo/bar
            | | | | | | |-route bar3 /foo/bar

EOF;

        $this->assertSame($expect, $result->getRoot()->toString(function($route) {
            if ($route instanceof DumperPrefixCollection) {
                return sprintf("coll %s", $route->getPrefix());
            } else {
                return sprintf("route %s %s", $route->getName(), $route->getRoute()->getPattern());
            }
        }, '            '));
    }

}

