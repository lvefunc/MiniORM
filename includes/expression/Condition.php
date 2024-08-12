<?php

namespace MiniORM\Expression;

class Condition implements IExpression {
    public const EqualTo            = "=";
    public const MoreThan           = ">";
    public const LessThan           = "<";
    public const MoreThanOrEqualTo  = ">=";
    public const LessThanOrEqualTo  = "<=";

    private string $property;
    private string $operand;
    private $value;

    /**
     * @param string $property
     * @param string $operand
     * @param mixed $value
     */
    public function __construct( string $property, string $operand, $value ) {
        $this->property = $property;
        $this->operand = $operand;
        $this->value = $value;
    }

    public function evaluate() : string {
        return $this->property . " " . $this->operand . " " . ( gettype( $this->value ) === "string" ? ( "\"" . $this->value . "\"" ) : $this->value );
    }
}