<?php

namespace MiniORM\Expression;

class Disjunction implements IExpression {
    /**
     * @var IExpression[]
     */
    private array $expressions = [];

    public function add( IExpression $expression ) : Disjunction {
        $this->expressions[] = $expression;

        return $this;
    }

    public function evaluate() : string {
        $result = "(";

        for ( $i = 0; $i < count( $this->expressions ); $i++ ) {
            $result .= ( $i !== 0 ? " OR " : "" ) . $this->expressions[$i]->evaluate();
        }

        return $result . ")";
    }
}