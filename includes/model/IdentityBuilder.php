<?php

namespace MiniORM\Model;

use MiniORM\Annotation\AnnotationParser;
use MiniORM\Annotation\BaseClass;
use MiniORM\Annotation\Table;
use MiniORM\Model\Property\Property;
use MiniORM\Model\Property\PropertyBuilder;
use MiniORM\UnitOfWork;
use MWException;
use ReflectionClass;
use ReflectionException;

class IdentityBuilder {
    private UnitOfWork $unitOfWork;
    private ReflectionClass $reflectionClass;

    private ?string $baseClassName = null;

    private string $tableName;
    private array $properties = [];

    /**
     * @param UnitOfWork $unitOfWork
     * @param object|string $objectOrClass
     *
     * @throws ReflectionException
     * @throws MWException
     */
    public function __construct( UnitOfWork $unitOfWork, $objectOrClass ) {
        $this->setUnitOfWork( $unitOfWork );
        $this->setReflectionClass( new ReflectionClass( $objectOrClass ) );
    }

    public function getUnitOfWork() : UnitOfWork {
        return $this->unitOfWork;
    }

    public function setUnitOfWork( UnitOfWork $unitOfWork ) : IdentityBuilder {
        $this->unitOfWork = $unitOfWork;

        return $this;
    }

    public function getReflectionClass() : ReflectionClass {
        return $this->reflectionClass;
    }

    /**
     * @throws MWException
     */
    public function setReflectionClass( ReflectionClass $reflectionClass ) : IdentityBuilder {
        $this->reflectionClass = $reflectionClass;
        $annotations = AnnotationParser::getInstance()->match( $reflectionClass->getDocComment() );

        foreach ( $annotations as $annotation ) {
            switch ( get_class( $annotation ) ) {
                case BaseClass::class:
                    $this->baseClassName = $annotation->getName();
                    break;
                case Table::class:
                    $this->tableName = $annotation->getName();
                    break;
                default:
                    throw new MWException( "Unsupported annotation type" );
            }
        }

        $reflectionProperties = $this->reflectionClass->getProperties();

        foreach ( $reflectionProperties as $reflectionProperty ) {
            if ( $reflectionProperty->getName() === "id" ) {
                $this->addProperty(
                    ( new PropertyBuilder() )
                        ->setReflectionProperty( $reflectionProperty )
                        ->setColumnName( "id" )
                        ->setColumnType( "int" )
                        ->setColumnLength( 10 )
                        ->setColumnNullable( false )
                        ->build()
                );

                continue;
            }

            $currentClass = $this->reflectionClass->getName();
            $declaringClass = $reflectionProperty->getDeclaringClass()->getName();

            if ( $currentClass !== $declaringClass ) {
                continue;
            }

            $property = ( new PropertyBuilder() )->setReflectionProperty( $reflectionProperty )->build();

            if ( $property->getColumnName() === null && $property->getAssociation() === null ) {
                continue;
            }

            $this->addProperty( $property );
        }

        return $this;
    }

    public function getBaseClassName() : ?string {
        return $this->baseClassName;
    }

    public function setBaseClassName( ?string $baseClassName ) : IdentityBuilder {
        $this->baseClassName = $baseClassName;

        return $this;
    }

    public function getTableName() : string {
        return $this->tableName;
    }

    public function setTableName( string $tableName ) : IdentityBuilder {
        $this->tableName = $tableName;

        return $this;
    }

    public function getProperties() : array {
        return $this->properties;
    }

    public function addProperty( Property $property ) {
        $this->properties[$property->getName()] = $property;
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function build() : Identity {
        return new Identity( $this );
    }
}