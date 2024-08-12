<?php

namespace MiniORM\Annotation;

use MWException;

abstract class Annotation implements IAnnotation {
    /**
     * @throws MWException
     */
    protected function getAttribute( array $attributes, string $attributeName ) {
        if ( !isset( $attributes[$attributeName] ) ) {
            throw new MWException( "Attribute by name \"" . $attributeName . "\" was required but was not found" );
        }

        return $attributes[$attributeName];
    }

    protected function getOptionalAttribute( array $attributes, string $attributeName ) {
        return $attributes[$attributeName] ?? null;
    }
}