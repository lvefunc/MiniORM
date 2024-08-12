<?php

namespace MiniORM\Model\Property;

use MiniORM\Entity;
use MiniORM\Model\Association\Association;
use MiniORM\Model\Property\Transform\ITransformStrategy;
use ReflectionProperty;

class Property {
    private ReflectionProperty $reflectionProperty;

    private ?string $columnName;
    private ?string $columnType;
    private ?int $columnLength;
    private ?bool $columnNullable;
    private ?Association $association;

    private ITransformStrategy $transformStrategy;

    public function __construct( PropertyBuilder $propertyBuilder ) {
        $this->reflectionProperty = $propertyBuilder->getReflectionProperty();
        $this->columnName = $propertyBuilder->getColumnName();
        $this->columnType = $propertyBuilder->getColumnType();
        $this->columnLength = $propertyBuilder->getColumnLength();
        $this->columnNullable = $propertyBuilder->isColumnNullable();
        $this->association = $propertyBuilder->getAssociation();
        $this->transformStrategy = $propertyBuilder->getTransformStrategy();
    }

    public function getValue( Entity $entity ) {
        $reflectionProperty = $this->reflectionProperty;
        $reflectionProperty->setAccessible( true );

        if ( !$reflectionProperty->isInitialized( $entity ) ) {
            return null;
        } else {
            $value = $reflectionProperty->getValue( $entity );

            if ( is_null( $value ) ) {
                return null;
            } else {
                return $this->transformStrategy->propertyToColumn( $reflectionProperty->getValue( $entity ) );
            }
        }
    }

    public function setValue( Entity $entity, $value ) {
        $reflectionProperty = $this->reflectionProperty;
        $reflectionProperty->setAccessible( true );
        $reflectionProperty->setValue(
            $entity,
            is_null( $value ) ? null : $this->transformStrategy->columnToProperty( $value )
        );
    }

    public function getName() : string {
        return $this->reflectionProperty->getName();
    }

    public function getType() : string {
        return $this->reflectionProperty->getType()->getName();
    }

    public function isPersistent() : bool {
        return $this->columnName !== null;
    }

    public function getColumnName() : ?string {
        return $this->columnName;
    }

    public function getColumnType() : ?string {
        return $this->columnType;
    }

    public function getColumnLength() : ?int {
        return $this->columnLength;
    }

    public function isColumnNullable() : ?bool {
        return $this->columnNullable;
    }

    public function hasAssociation() : bool {
        return $this->association !== null;
    }

    public function getAssociation() : ?Association {
        return $this->association;
    }
}