<?php

namespace MiniORM\Annotation;

use MWException;

/**
 * TODO: Refactor to BaseEntity
 */
class BaseClass extends Annotation {
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