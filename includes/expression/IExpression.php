<?php

namespace MiniORM\Expression;

interface IExpression {
    public function evaluate() : string;
}