<?php

namespace MiniORM\Model\Property;

use MiniORM\Annotation\AnnotationParser;
use MiniORM\Annotation\Column;
use MiniORM\Annotation\ManyToOne;
use MiniORM\Annotation\OneToMany;
use MiniORM\Annotation\OneToOne;
use MiniORM\Model\Association\Association;
use MiniORM\Model\Association\AssociationBuilder;
use MiniORM\Model\Property\Transform\ITransformStrategy;
use MiniORM\Model\Property\Transform\TransformStrategyRegistry;
use MWException;
use ReflectionProperty;

class PropertyBuilder {
    private ReflectionProperty $reflectionProperty;

    private ?string $columnName = null;
    private ?string $columnType = null;
    private ?int $columnLength = null;
    private ?bool $columnNullable = null;
    private ?Association $association = null;

    private ITransformStrategy $transformStrategy;

    public function getReflectionProperty() : ReflectionProperty {
        return $this->reflectionProperty;
    }

    /**
     * @throws MWException
     */
    public function setReflectionProperty( ReflectionProperty $reflectionProperty ) : PropertyBuilder {
        $this->reflectionProperty = $reflectionProperty;
        $annotations = AnnotationParser::getInstance()->match( $this->reflectionProperty->getDocComment() );

        foreach ( $annotations as $annotation ) {
            switch ( get_class( $annotation ) ) {
                case Column::class:
                    $this->setColumnName( $annotation->getName() );
                    $this->setColumnType( $annotation->getType() );
                    $this->setColumnLength( $annotation->getLength() );
                    $this->setColumnNullable( $annotation->isNullable() );
                    break;
                case OneToOne::class:
                case OneToMany::class:
                case ManyToOne::class:
                    $this->setAssociation( ( new AssociationBuilder() )->setFromAnnotation( $annotation )->build() );
                    break;
                default:
                    throw new MWException( "Unsupported annotation type" );
            }
        }

        $typeName = $this->reflectionProperty->getType()->getName();
        $this->setTransformStrategy( TransformStrategyRegistry::getInstance()->getTransformStrategy( $typeName ) );

        return $this;
    }

    public function getColumnName() : ?string {
        return $this->columnName;
    }

    public function setColumnName( ?string $columnName ) : PropertyBuilder {
        $this->columnName = $columnName;

        return $this;
    }

    public function getColumnType() : ?string {
        return $this->columnType;
    }

    public function setColumnType( ?string $columnType ) : PropertyBuilder {
        $this->columnType = $columnType;

        return $this;
    }

    public function getColumnLength() : ?int {
        return $this->columnLength;
    }

    public function setColumnLength( ?int $columnLength ) : PropertyBuilder {
        $this->columnLength = $columnLength;

        return $this;
    }

    public function isColumnNullable() : ?bool {
        return $this->columnNullable;
    }

    public function setColumnNullable( ?bool $columnNullable ) : PropertyBuilder {
        $this->columnNullable = $columnNullable;

        return $this;
    }

    public function getAssociation() : ?Association {
        return $this->association;
    }

    public function setAssociation( ?Association $association ) : PropertyBuilder {
        $this->association = $association;

        return $this;
    }

    public function getTransformStrategy() : ITransformStrategy {
        return $this->transformStrategy;
    }

    public function setTransformStrategy( ITransformStrategy $transformStrategy ) : PropertyBuilder {
        $this->transformStrategy = $transformStrategy;

        return $this;
    }

    public function build() : Property {
        return new Property( $this );
    }
}