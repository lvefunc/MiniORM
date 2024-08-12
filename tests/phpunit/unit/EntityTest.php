<?php

use MiniORM\Entity;

class A extends Entity {
}

class B extends Entity {
}

/**
 * @covers \MiniORM\Entity
 */
class EntityTest extends MediaWikiUnitTestCase {
    public function testEquals() {
        $a = new A();
        $b = new B();

        $this->assertTrue( $a->equals( $a ) );
        $this->assertTrue( $b->equals( $b ) );
        $this->assertFalse( $a->equals( $b ) );
    }
}