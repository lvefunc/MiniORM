<?php

namespace MiniORM\Model\Property\Transform;

class DefaultTransformStrategy implements ITransformStrategy {
    public function propertyToColumn( $value ) {
        return $value;
    }

    public function columnToProperty( $value ) {
        return $value;
    }
}