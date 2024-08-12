<?php

namespace MiniORM\Model\Property\Transform;

interface ITransformStrategy {
    public function propertyToColumn( $value );
    public function columnToProperty( $value );
}