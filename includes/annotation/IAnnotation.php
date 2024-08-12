<?php

namespace MiniORM\Annotation;

interface IAnnotation {
    public function __construct( array $attributes );
}