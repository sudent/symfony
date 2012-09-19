<?php

namespace Symfony\Component\Routing\Test\Matcher\Dumper;

use Symfony\Component\Routing\Matcher\Dumper\DumperCollection;

class DumperCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCloneHierarchyUntil()
    {
        $a = new DumperCollection();
        $a->set('name', 'a');

        $b = new DumperCollection();
        $b->set('name', 'b');
        $a->addRoute($b);

        $c = new DumperCollection();
        $c->set('name', 'c');
        $b->addRoute($c);

        $d = new DumperCollection();
        $d->set('name', 'd');
        $c->addRoute($d);

        $d2 = $d->cloneHierarchyUntil();
        $c2 = $d2->getParent();
        $b2 = $c2->getParent();
        $a2 = $b2->getParent();

        $this->assertNotSame($d, $d2);
        $this->assertSame('d', $d2->get('name'));

        $this->assertNotSame($c, $c2);
        $this->assertSame('c', $c2->get('name'));

        $this->assertNotSame($b, $b2);
        $this->assertSame('b', $b2->get('name'));

        $this->assertNotSame($a, $b2);
        $this->assertSame('a', $a2->get('name'));

        $this->assertNull($a2->getParent());

        $d2 = $d->cloneHierarchyUntil($b);
        $c2 = $d2->getParent();
        $b2 = $c2->getParent();

        $this->assertNotSame($d, $d2);
        $this->assertSame('d', $d2->get('name'));

        $this->assertNotSame($c, $c2);
        $this->assertSame('c', $c2->get('name'));

        $this->assertNull($b2);
    }

    public function testGetRoot()
    {
        $a = new DumperCollection();

        $b = new DumperCollection();
        $a->addRoute($b);

        $c = new DumperCollection();
        $b->addRoute($c);

        $d = new DumperCollection();
        $c->addRoute($d);

        $this->assertSame($a, $c->getRoot());
    }
}

