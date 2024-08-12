<?php

namespace MiniORM\Annotation;

class OneToOne extends Association {
    private ?string $mappedBy;

    public function __construct( array $attributes ) {
        parent::__construct( $attributes );
        $this->mappedBy = $this->getOptionalAttribute( $attributes, "mappedBy" );
    }

    public function getMappedBy() : ?string {
        return $this->mappedBy;
    }
}