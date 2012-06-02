<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Tests;

use Symfony\Component\Routing\Route;

class RouteCompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideCompileData
     */
    public function testCompile($name, $arguments, $prefix, $regex, $variables, $tokens)
    {
        $r = new \ReflectionClass('Symfony\\Component\\Routing\\Route');
        $route = $r->newInstanceArgs($arguments);

        $compiled = $route->compile();
        $this->assertEquals($prefix, $compiled->getStaticPrefix(), $name.' (static prefix)');
        $this->assertEquals($regex, $compiled->getRegex(), $name.' (regex)');
        $this->assertEquals($variables, $compiled->getVariables(), $name.' (variables)');
        $this->assertEquals($tokens, $compiled->getTokens(), $name.' (tokens)');
    }

    public function provideCompileData()
    {
        return array(
            array(
                'Static route',
                array('/foo'),
                '/foo', '#^/foo$#s', array(), array(
                    array('text', '/foo'),
                )),

            array(
                'Route with a variable',
                array('/foo/{bar}'),
                '/foo', '#^/foo/(?<bar>[^/]+)$#s', array('bar'), array(
                    array('variable', '/', '[^/]+', 'bar'),
                    array('text', '/foo'),
                )),

            array(
                'Route with a variable that has a default value',
                array('/foo/{bar}', array('bar' => 'bar')),
                '/foo', '#^/foo(?:/(?<bar>[^/]+))?$#s', array('bar'), array(
                    array('variable', '/', '[^/]+', 'bar'),
                    array('text', '/foo'),
                )),

            array(
                'Route with several variables',
                array('/foo/{bar}/{foobar}'),
                '/foo', '#^/foo/(?<bar>[^/]+)/(?<foobar>[^/]+)$#s', array('bar', 'foobar'), array(
                    array('variable', '/', '[^/]+', 'foobar'),
                    array('variable', '/', '[^/]+', 'bar'),
                    array('text', '/foo'),
                )),

            array(
                'Route with several variables that have default values',
                array('/foo/{bar}/{foobar}', array('bar' => 'bar', 'foobar' => '')),
                '/foo', '#^/foo(?:/(?<bar>[^/]+)(?:/(?<foobar>[^/]+))?)?$#s', array('bar', 'foobar'), array(
                    array('variable', '/', '[^/]+', 'foobar'),
                    array('variable', '/', '[^/]+', 'bar'),
                    array('text', '/foo'),
                )),

            array(
                'Route with several variables but some of them have no default values',
                array('/foo/{bar}/{foobar}', array('bar' => 'bar')),
                '/foo', '#^/foo/(?<bar>[^/]+)/(?<foobar>[^/]+)$#s', array('bar', 'foobar'), array(
                    array('variable', '/', '[^/]+', 'foobar'),
                    array('variable', '/', '[^/]+', 'bar'),
                    array('text', '/foo'),
                )),

            array(
                'Route with an optional variable as the first segment',
                array('/{bar}', array('bar' => 'bar')),
                '', '#^/(?<bar>[^/]+)?$#s', array('bar'), array(
                    array('variable', '/', '[^/]+', 'bar'),
                )),

            array(
                'Route with an optional variable as the first segment with requirements',
                array('/{bar}', array('bar' => 'bar'), array('bar' => '(foo|bar)')),
                '', '#^/(?<bar>(foo|bar))?$#s', array('bar'), array(
                    array('variable', '/', '(foo|bar)', 'bar'),
                )),

            array(
                'Route with only optional variables',
                array('/{foo}/{bar}', array('foo' => 'foo', 'bar' => 'bar')),
                '', '#^/(?<foo>[^/]+)?(?:/(?<bar>[^/]+))?$#s', array('foo', 'bar'), array(
                    array('variable', '/', '[^/]+', 'bar'),
                    array('variable', '/', '[^/]+', 'foo'),
                )),

            array(
                'Route with a variable in last position',
                array('/foo-{bar}'),
                '/foo', '#^/foo\-(?<bar>[^\-]+)$#s', array('bar'), array(
                array('variable', '-', '[^\-]+', 'bar'),
                array('text', '/foo'),
            )),

            array(
                'Route with a format',
                array('/foo/{bar}.{_format}'),
                '/foo', '#^/foo/(?<bar>[^/\.]+)\.(?<_format>[^\.]+)$#s', array('bar', '_format'), array(
                array('variable', '.', '[^\.]+', '_format'),
                array('variable', '/', '[^/\.]+', 'bar'),
                array('text', '/foo'),
            )),
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testRouteWithSameVariableTwice()
    {
        $route = new Route('/{name}/{name}');

        $compiled = $route->compile();
    }

    /**
     * @dataProvider provideCompileExtendedData
     */
    public function testCompileExtended($name, $arguments, $prefix, $regex, $variables, $pathVariables, $tokens, $hostnameRegex, $hostnameVariables, $hostnameTokens)
    {
        $r = new \ReflectionClass('Symfony\\Component\\Routing\\Route');
        $route = $r->newInstanceArgs($arguments);

        $compiled = $route->compile();
        $this->assertEquals($prefix, $compiled->getStaticPrefix(), $name.' (static prefix)');
        $this->assertEquals($regex, str_replace(array("\n", ' '), '', $compiled->getRegex()), $name.' (regex)');
        $this->assertEquals($variables, $compiled->getVariables(), $name.' (variables)');
        $this->assertEquals($pathVariables, $compiled->getPathVariables(), $name.' (path variables)');
        $this->assertEquals($tokens, $compiled->getTokens(), $name.' (tokens)');
        $this->assertEquals($hostnameRegex, str_replace(array("\n", ' '), '', $compiled->getHostnameRegex()), $name.' (hostname regex)');
        $this->assertEquals($hostnameVariables, $compiled->getHostnameVariables(), $name.' (hostname variables)');
        $this->assertEquals($hostnameTokens, $compiled->getHostnameTokens(), $name.' (hostname tokens)');
    } 

    public function provideCompileExtendedData()
    {
        return array(
            array(
                'Route with hostname pattern',
                array('/hello', array(), array(), array(), 'www.example.com'),
                '/hello', '#^/hello$#s', array(), array(), array(
                    array('text', '/hello'),
                ),
                '#^www\.example\.com$#s', array(), array(
                    array('text', 'www.example.com'),
                ),
            ),
            array(
                'Route with hostname pattern and some variables',
                array('/hello/{name}', array(), array(), array(), 'www.example.{tld}'),
                '/hello', '#^/hello/(?<name>[^/]+?)$#s', array('tld', 'name'), array('name'), array(
                    array('variable', '/', '[^/]+?', 'name'),
                    array('text', '/hello'),
                ),
                '#^www\.example\.(?<tld>[^\.]+?)$#s', array('tld'), array(
                    array('variable', '.', '[^\.]+?', 'tld'),
                    array('text', 'www.example'),
                ),
            ),
            array(
                'Route with variable at begining of hostname',
                array('/hello', array(), array(), array(), '{locale}.example.{tld}'),
                '/hello', '#^/hello$#s', array('locale', 'tld'), array(), array(
                    array('text', '/hello'),
                ),
                '#^(?<locale>[^\.]+?)\.example\.(?<tld>[^\.]+?)$#s', array('locale', 'tld'), array(
                    array('variable', '.', '[^\.]+?', 'tld'),
                    array('text', '.example'),
                    array('variable', '', '[^\.]+?', 'locale'),
                ),
            ),
            array(
                'Route with hostname variables that has a default value',
                array('/hello', array('locale' => 'a', 'tld' => 'b'), array(), array(), '{locale}.example.{tld}'),
                '/hello', '#^/hello$#s', array('locale', 'tld'), array(), array(
                    array('text', '/hello'),
                ),
                '#^(?<locale>[^\.]+?)\.example\.(?<tld>[^\.]+?)$#s', array('locale', 'tld'), array(
                    array('variable', '.', '[^\.]+?', 'tld'),
                    array('text', '.example'),
                    array('variable', '', '[^\.]+?', 'locale'),
                ),
            ),            
        );
    }
}

