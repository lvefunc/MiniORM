<?php

namespace MiniORM\Annotation;

class OneToMany extends Association {
    private string $mappedBy;

    public function __construct( array $attributes ) {
        parent::__construct( $attributes );
        $this->mappedBy = $this->getAttribute( $attributes, "mappedBy" );
    }

    public function getMappedBy() {
        return $this->mappedBy;
    }
}