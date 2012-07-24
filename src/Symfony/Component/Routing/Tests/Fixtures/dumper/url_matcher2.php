<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * ProjectUrlMatcher
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class ProjectUrlMatcher extends Symfony\Component\Routing\Tests\Fixtures\RedirectableUrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($pathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($pathinfo);

        // foo
        if (0 === strpos($pathinfo, '/foo') && preg_match('#^/foo/(?<bar>baz|symfony)$#s', $pathinfo, $matches)) {
            return array_merge($this->mergeDefaults($matches, array (  'def' => 'test',)), array('_route' => 'foo'));
        }

        if (0 === strpos($pathinfo, '/bar')) {
            // bar
            if (preg_match('#^/bar/(?<foo>[^/]+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_bar;
                }

                $matches['_route'] = 'bar';

                return $matches;
            }
            not_bar:

            // barhead
            if (0 === strpos($pathinfo, '/barhead') && preg_match('#^/barhead/(?<foo>[^/]+)$#s', $pathinfo, $matches)) {
                if (!in_array($this->context->getMethod(), array('GET', 'HEAD'))) {
                    $allow = array_merge($allow, array('GET', 'HEAD'));
                    goto not_barhead;
                }

                $matches['_route'] = 'barhead';

                return $matches;
            }
            not_barhead:

        }

        if (0 === strpos($pathinfo, '/test')) {
            if (0 === strpos($pathinfo, '/test/baz')) {
                // baz
                if ($pathinfo === '/test/baz') {
                    return array('_route' => 'baz');
                }

                // baz2
                if ($pathinfo === '/test/baz.html') {
                    return array('_route' => 'baz2');
                }

                // baz3
                if (rtrim($pathinfo, '/') === '/test/baz3') {
                    if (substr($pathinfo, -1) !== '/') {
                        return $this->redirect($pathinfo.'/', 'baz3');
                    }

                    return array('_route' => 'baz3');
                }

            }

            // baz4
            if (preg_match('#^/test/(?<foo>[^/]+)/?$#s', $pathinfo, $matches)) {
                if (substr($pathinfo, -1) !== '/') {
                    return $this->redirect($pathinfo.'/', 'baz4');
                }

                $matches['_route'] = 'baz4';

                return $matches;
            }

            // baz5
            if (preg_match('#^/test/(?<foo>[^/]+)/$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'POST') {
                    $allow[] = 'POST';
                    goto not_baz5;
                }

                $matches['_route'] = 'baz5';

                return $matches;
            }
            not_baz5:

            // baz.baz6
            if (preg_match('#^/test/(?<foo>[^/]+)/$#s', $pathinfo, $matches)) {
                if ($this->context->getMethod() != 'PUT') {
                    $allow[] = 'PUT';
                    goto not_bazbaz6;
                }

                $matches['_route'] = 'baz.baz6';

                return $matches;
            }
            not_bazbaz6:

        }

        // foofoo
        if ($pathinfo === '/foofoo') {
            return array (  'def' => 'test',  '_route' => 'foofoo',);
        }

        // quoter
        if (preg_match('#^/(?<quoter>[\']+)$#s', $pathinfo, $matches)) {
            $matches['_route'] = 'quoter';

            return $matches;
        }

        // space
        if ($pathinfo === '/spa ce') {
            return array('_route' => 'space');
        }

        if (0 === strpos($pathinfo, '/a')) {
            if (0 === strpos($pathinfo, '/a/b\'b')) {
                // foo1
                if (preg_match('#^/a/b\'b/(?<foo>[^/]+)$#s', $pathinfo, $matches)) {
                    $matches['_route'] = 'foo1';

                    return $matches;
                }

                // bar1
                if (preg_match('#^/a/b\'b/(?<bar>[^/]+)$#s', $pathinfo, $matches)) {
                    $matches['_route'] = 'bar1';

                    return $matches;
                }

            }

            // overriden
            if (preg_match('#^/a/(?<var>.*)$#s', $pathinfo, $matches)) {
                $matches['_route'] = 'overriden';

                return $matches;
            }

            if (0 === strpos($pathinfo, '/a/b\'b')) {
                // foo2
                if (preg_match('#^/a/b\'b/(?<foo1>[^/]+)$#s', $pathinfo, $matches)) {
                    $matches['_route'] = 'foo2';

                    return $matches;
                }

                // bar2
                if (preg_match('#^/a/b\'b/(?<bar1>[^/]+)$#s', $pathinfo, $matches)) {
                    $matches['_route'] = 'bar2';

                    return $matches;
                }

            }

        }

        if (0 === strpos($pathinfo, '/multi')) {
            // helloWorld
            if (0 === strpos($pathinfo, '/multi/hello') && preg_match('#^/multi/hello(?:/(?<who>[^/]+))?$#s', $pathinfo, $matches)) {
                return array_merge($this->mergeDefaults($matches, array (  'who' => 'World!',)), array('_route' => 'helloWorld'));
            }

            // overriden2
            if ($pathinfo === '/multi/new') {
                return array('_route' => 'overriden2');
            }

            // hey
            if (rtrim($pathinfo, '/') === '/multi/hey') {
                if (substr($pathinfo, -1) !== '/') {
                    return $this->redirect($pathinfo.'/', 'hey');
                }

                return array('_route' => 'hey');
            }

        }

        // foo3
        if (preg_match('#^/(?<_locale>[^/]+)/b/(?<foo>[^/]+)$#s', $pathinfo, $matches)) {
            $matches['_route'] = 'foo3';

            return $matches;
        }

        // bar3
        if (preg_match('#^/(?<_locale>[^/]+)/b/(?<bar>[^/]+)$#s', $pathinfo, $matches)) {
            $matches['_route'] = 'bar3';

            return $matches;
        }

        if (0 === strpos($pathinfo, '/aba')) {
            // ababa
            if ($pathinfo === '/ababa') {
                return array('_route' => 'ababa');
            }

            // foo4
            if (preg_match('#^/aba/(?<foo>[^/]+)$#s', $pathinfo, $matches)) {
                $matches['_route'] = 'foo4';

                return $matches;
            }

        }

        $hostname = $this->context->getHost();

        if (preg_match('#^a\\.example\\.com$#s', $hostname, $hostnameMatches)) {
            // route1
            if ($pathinfo === '/route1') {
                return array('_route' => 'route1');
            }

            // route2
            if ($pathinfo === '/c2/route2') {
                return array('_route' => 'route2');
            }

        }

        if (preg_match('#^b\\.example\\.com$#s', $hostname, $hostnameMatches)) {
            // route3
            if ($pathinfo === '/c2/route3') {
                return array('_route' => 'route3');
            }

        }

        if (preg_match('#^a\\.example\\.com$#s', $hostname, $hostnameMatches)) {
            // route4
            if ($pathinfo === '/route4') {
                return array('_route' => 'route4');
            }

        }

        if (preg_match('#^c\\.example\\.com$#s', $hostname, $hostnameMatches)) {
            // route5
            if ($pathinfo === '/route5') {
                return array('_route' => 'route5');
            }

        }

        // route6
        if ($pathinfo === '/route6') {
            return array('_route' => 'route6');
        }

        if (preg_match('#^(?<var1>[^\\.]+)\\.example\\.com$#s', $hostname, $hostnameMatches)) {
            if (0 === strpos($pathinfo, '/route1')) {
                // route11
                if ($pathinfo === '/route11') {
                    $matches = $hostnameMatches;
                    $matches['_route'] = 'route11';

                    return $matches;
                }

                // route12
                if ($pathinfo === '/route12') {
                    return array_merge($this->mergeDefaults($hostnameMatches, array (  'var1' => 'val',)), array('_route' => 'route12'));
                }

                // route13
                if (0 === strpos($pathinfo, '/route13') && preg_match('#^/route13/(?<name>[^/]+)$#s', $pathinfo, $matches)) {
                    $matches = $matches + $hostnameMatches;
                    $matches['_route'] = 'route13';

                    return $matches;
                }

                // route14
                if (0 === strpos($pathinfo, '/route14') && preg_match('#^/route14/(?<name>[^/]+)$#s', $pathinfo, $matches)) {
                    return array_merge($this->mergeDefaults($matches + $hostnameMatches, array (  'var1' => 'val',)), array('_route' => 'route14'));
                }

            }

        }

        if (preg_match('#^c\\.example\\.com$#s', $hostname, $hostnameMatches)) {
            // route15
            if (0 === strpos($pathinfo, '/route15') && preg_match('#^/route15/(?<name>[^/]+)$#s', $pathinfo, $matches)) {
                $matches['_route'] = 'route15';

                return $matches;
            }

        }

        if (0 === strpos($pathinfo, '/route1')) {
            // route16
            if (0 === strpos($pathinfo, '/route16') && preg_match('#^/route16/(?<name>[^/]+)$#s', $pathinfo, $matches)) {
                return array_merge($this->mergeDefaults($matches, array (  'var1' => 'val',)), array('_route' => 'route16'));
            }

            // route17
            if ($pathinfo === '/route17') {
                return array('_route' => 'route17');
            }

        }

        if (0 === strpos($pathinfo, '/a')) {
            // a
            if ($pathinfo === '/a/a...') {
                return array('_route' => 'a');
            }

            if (0 === strpos($pathinfo, '/a/b')) {
                // b
                if (preg_match('#^/a/b/(?<var>[^/]+)$#s', $pathinfo, $matches)) {
                    $matches['_route'] = 'b';

                    return $matches;
                }

                // c
                if (0 === strpos($pathinfo, '/a/b/c') && preg_match('#^/a/b/c/(?<var>[^/]+)$#s', $pathinfo, $matches)) {
                    $matches['_route'] = 'c';

                    return $matches;
                }

            }

        }

        // secure
        if ($pathinfo === '/secure') {
            if ($this->context->getScheme() !== 'https') {
                return $this->redirect($pathinfo, 'secure', 'https');
            }

            return array('_route' => 'secure');
        }

        // nonsecure
        if ($pathinfo === '/nonsecure') {
            if ($this->context->getScheme() !== 'http') {
                return $this->redirect($pathinfo, 'nonsecure', 'http');
            }

            return array('_route' => 'nonsecure');
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
