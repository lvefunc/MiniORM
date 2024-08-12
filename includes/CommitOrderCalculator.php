<?php

namespace MiniORM;

class CommitOrderCalculator {
    private array $nodes = [];

    public function addNode( string $node ) : bool {
        if ( !isset( $this->nodes[$node] ) ) {
            $this->nodes[$node] = [];

            return true;
        }

        return false;
    }

    public function addDependency( string $node, string $dependency ) : bool {
        $this->addNode( $node );

        if ( !in_array( $dependency, $this->nodes[$node] ) ) {
            $this->nodes[$node][] = $dependency;

            return true;
        }

        return false;
    }

    public function calculate() : array {
        $commitOrder = [];
        $visitedNodes = [];

        foreach ( array_keys( $this->nodes ) as $node ) {
            if ( !in_array( $node, $visitedNodes ) ) {
                $this->dfs( $node, $commitOrder, $visitedNodes );
            }
        }

        return $commitOrder;
    }

    public function dfs( string $node, array &$commitOrder, array &$visitedNodes ) {
        if ( in_array( $node, $visitedNodes ) ) {
            return;
        }

        $visitedNodes[] = $node;

        foreach ( $this->nodes[$node] as $dependency ) {
            $this->dfs( $dependency, $commitOrder, $visitedNodes );
        }

        $commitOrder[] = $node;
    }
}