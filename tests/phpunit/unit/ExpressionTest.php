<?php

use MiniORM\Expression\Condition;
use MiniORM\Expression\Conjunction;
use MiniORM\Expression\Disjunction;

class ExpressionTest extends MediaWikiUnitTestCase {
    public function testExpression() {
        $expression =
            ( new Disjunction() )
                ->add(
                    ( new Conjunction() )
                        ->add( ( new Condition( "id", Condition::MoreThan, 10 ) ) )
                        ->add( ( new Condition( "id", Condition::LessThan, 20 ) ) )
                )
                ->add(
                    ( new Condition( "name", Condition::EqualTo, "Test" ) )
                );
        $this->assertEquals( "((id > 10 AND id < 20) OR name = \"Test\")", $expression->evaluate() );
    }
}