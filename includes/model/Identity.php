<?php

namespace MiniORM\Model;

use Exception;
use MiniORM\Entity;
use MiniORM\Model\Association\ManyToOneAssociation;
use MiniORM\Model\Association\OneToManyAssociation;
use MiniORM\Model\Association\OneToOneAssociation;
use MiniORM\Model\Property\Property;
use MiniORM\UnitOfWork;
use MWException;
use ReflectionClass;
use ReflectionException;

class Identity {
    private UnitOfWork $unitOfWork;
    private ReflectionClass $reflectionClass;

    /** @var Identity|null */
    private Identity $baseClass;
    /** @var array<string, Identity> */
    private array $subClasses = [];

    /** @var string */
    private string $tableName;
    /** @var array<string, Property> */
    private array $properties;

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function __construct( IdentityBuilder $identityBuilder ) {
        $this->unitOfWork = $identityBuilder->getUnitOfWork();
        $this->reflectionClass = $identityBuilder->getReflectionClass();

        if ( $identityBuilder->getBaseClassName() !== null ) {
            $this->baseClass = $this->unitOfWork->getIdentity( $identityBuilder->getBaseClassName() );
            $this->baseClass->addSubClass( $this );
        }

        $this->tableName = $identityBuilder->getTableName();
        $this->properties = $identityBuilder->getProperties();
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     * @throws Exception
     */
    public function newInstance() : Entity {
        $instance = $this->reflectionClass->newInstanceWithoutConstructor();

        if ( !( $instance instanceof Entity ) ) {
            throw new MWException( "Must be an instance of Entity" );
        }

        $hash = $this->reflectionClass->getProperty( "hash" );
        $hash->setAccessible( true );
        $hash->setValue( $instance, bin2hex( random_bytes( 20 ) ) );

        return $instance;
    }

    public function serialize( Entity $entity ) : array {
        $result = [];

        $properties = $this->properties;
        $identity = $this;

        while ( !is_null( $identity->getBaseClass() ) ) {
            $identity = $identity->getBaseClass();

            foreach ( $identity->properties as $name => $property ) {
                if ( !isset( $properties[$name] ) ) {
                    $properties[$name] = $property;
                }
            }
        }

        $identifier = $properties["id"];
        unset($properties["id"]);

        $result[$identifier->getName()] = $identifier->getValue( $entity );
        $result["class"] = $this->reflectionClass->getName();

        foreach ( $properties as $property ) {
            $value = $property->getValue( $entity );
            $value = is_bool( $value ) ? ( $value ? "true" : "false" ) : $value;

            if ( !$property->hasAssociation() || is_null( $value ) ) {
                $result[$property->getName()] = $value;

                continue;
            }

            $association = $property->getAssociation();

            if ( $association instanceof OneToOneAssociation ) {
                if ( is_null( $association->getMappedBy() ) ) {
                    $result[$property->getName()] = $value->serialize();
                } else {
                    $result[$property->getName() . "ID"] = $value->getID();
                }
            } else if ( $association instanceof ManyToOneAssociation ) {
                $result[$property->getName() . "ID"] = $value->getID();
            } else if ( $association instanceof OneToManyAssociation ) {
                $result[$property->getName()] = [];

                foreach ( $value as $entry ) {
                    $result[$property->getName()][] = $entry->serialize();
                }
            }
        }

        return $result;
    }

    public function getUnitOfWork() : UnitOfWork {
        return $this->unitOfWork;
    }

    public function getClassName() : string {
        return $this->reflectionClass->getName();
    }

    public function getBaseClass() : ?Identity {
        return $this->baseClass ?? null;
    }

    public function getRootClass() : Identity {
        return $this->getBaseClass() === null ? $this : $this->getBaseClass()->getRootClass();
    }

    public function hasSubClasses() : bool {
        return count( $this->subClasses ) > 0;
    }

    public function getSubClassNames() : array {
        return array_keys( $this->subClasses );
    }

    public function getSubClass( string $subClassName ) : ?Identity {
        return $this->subClasses[$subClassName] ?? null;
    }

    public function addSubClass( Identity $subClass ) {
        $this->subClasses[$subClass->getClassName()] = $subClass;
    }

    public function getTableName() : string {
        return $this->tableName;
    }

    public function getPropertyNames() : array {
        return array_keys( $this->properties );
    }

    public function getProperty( string $propertyName ) : ?Property {
        return $this->properties[$propertyName] ?? null;
    }
}