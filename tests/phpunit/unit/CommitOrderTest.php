<?php

use MiniORM\CommitOrderCalculator;

class CommitOrderTest extends MediaWikiUnitTestCase {
    /**
     * @covers MiniORM\CommitOrderCalculator
     */
    public function testCommitOrder() {
        $commitOrderCalculator = new CommitOrderCalculator();
        $commitOrderCalculator->addNode( "A" );
        $commitOrderCalculator->addNode( "B" );
        $commitOrderCalculator->addNode( "C" );
        $commitOrderCalculator->addNode( "D" );
        $commitOrderCalculator->addDependency( "D", "B" );
        $commitOrderCalculator->addDependency( "D", "C" );
        $commitOrderCalculator->addDependency( "C", "A" );
        $commitOrderCalculator->addDependency( "C", "B" );
        $commitOrderCalculator->addDependency( "B", "A" );
        $this->assertArrayEquals( [ "A", "B", "C", "D" ], $commitOrderCalculator->calculate() );
    }
}