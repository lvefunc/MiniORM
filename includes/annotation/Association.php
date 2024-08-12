<?php

namespace MiniORM\Annotation;

use MWException;

class Association extends Annotation {
    protected string $target;

    /**
     * @throws MWException
     */
    public function __construct( array $attributes ) {
        $this->target = $this->getAttribute( $attributes, "target" );
    }

    public function getTarget() : string {
        return $this->target;
    }
}