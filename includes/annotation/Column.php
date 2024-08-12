<?php

namespace MiniORM\Annotation;

use MWException;

class Column extends Annotation {
    private string $name;
    private ?string $type;
    private ?int $length;
    private ?bool $nullable;

    /**
     * @throws MWException
     */
    public function __construct( array $attributes ) {
        $this->name = $this->getAttribute( $attributes, "name" );
        $this->type = $this->getOptionalAttribute( $attributes, "type" );
        $this->length = $this->getOptionalAttribute( $attributes, "length" );
        $this->nullable = $this->getOptionalAttribute( $attributes, "nullable" );
    }

    public function getName() : string {
        return $this->name;
    }

    public function getType() : ?string {
        return $this->type;
    }

    public function getLength() : ?string {
        return $this->length;
    }

    public function isNullable() : ?bool {
        return $this->nullable;
    }
}