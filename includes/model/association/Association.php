<?php

namespace MiniORM\Model\Association;

abstract class Association {
    protected string $target;

    public function __construct( AssociationBuilder $associationBuilder ) {
        $this->target = $associationBuilder->getTarget();
    }

    public function getTarget() : string {
        return $this->target;
    }
}