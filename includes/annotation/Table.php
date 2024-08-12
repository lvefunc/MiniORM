<?php

namespace MiniORM\Annotation;

use MWException;

class Table extends Annotation {
    private string $name;

    /**
     * @throws MWException
     */
    public function __construct( array $attributes ) {
        $this->name = $this->getAttribute( $attributes, "name" );
    }

    public function getName() : string {
        return $this->name;
    }
}