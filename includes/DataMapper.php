<?php

namespace MiniORM;

use MiniORM\Expression\Condition;
use MiniORM\Expression\Conjunction;
use MiniORM\Expression\IExpression;
use MiniORM\Model\Association\ManyToOneAssociation;
use MiniORM\Model\Association\OneToManyAssociation;
use MiniORM\Model\Association\OneToOneAssociation;
use MiniORM\Model\Identity;
use MWException;
use ReflectionException;
use Wikimedia\Rdbms\SelectQueryBuilder;
use Workflows\Runtime\Context\Context;

class DataMapper {
    private UnitOfWork $unitOfWork;
    private Identity $identity;

    public function __construct( UnitOfWork $unitOfWork, Identity $identity ) {
        $this->unitOfWork = $unitOfWork;
        $this->identity = $identity;
    }

    public function count( ?IExpression $expression ) : int {
        $dbr = $this->unitOfWork->getDBR();
        $sqb = new SelectQueryBuilder( $dbr );
        $sqb->table( $this->identity->getTableName() );

        if ( !is_null( $expression ) )
            $sqb->where( $expression->evaluate() );

        return $sqb->fetchRowCount();
    }

    /**
     * @param IExpression $expression
     *
     * @return Entity|null
     * @throws ReflectionException
     * @throws MWException
     */
    public function findSingle( IExpression $expression ) : ?Entity {
        $dbr = $this->unitOfWork->getDBR();
        $row = $dbr->selectRow( $this->identity->getTableName(), $dbr::ALL_ROWS, [ $expression->evaluate() ] );

        if ( !$row ) {
            return null;
        }

        $cached = $this->unitOfWork->getCached( $this->identity->getClassName(), $row->id );

        if ( !is_null( $cached ) ) {
            return $cached;
        }

        if (
            property_exists( $row, "discriminator" ) &&
            !is_null( $row->discriminator )
        ) {
            $dataMapper = UnitOfWork::getInstance()->getDataMapper( $row->discriminator );

            return $dataMapper->findSingle( $expression );
        }

        $entity = $this->identity->newInstance();
        $this->mapFromRow( $entity, $row );
        $this->loadBaseClass( $entity );

        return $entity;
    }

    /**
     * @param IExpression $expression
     * @param array $options
     *
     * @return Entity[]
     * @throws MWException
     * @throws ReflectionException
     */
    public function findMultiple( IExpression $expression, array $options = [] ) : array {
        $dbr = $this->unitOfWork->getDBR();
        $rows = $dbr->select(
            $this->identity->getTableName(),
            $dbr::ALL_ROWS, [
                $expression->evaluate()
            ], __METHOD__, $options
        );

        $entities = [];

        foreach ( $rows as $row ) {
            $cached = $this->unitOfWork->getCached( $this->identity->getClassName(), $row->id );

            if ( !is_null( $cached ) ) {
                $entities[] = $cached;
                continue;
            }

            if (
                property_exists( $row, "discriminator" ) &&
                !is_null( $row->discriminator )
            ) {
                $dataMapper = UnitOfWork::getInstance()->getDataMapper( $row->discriminator );
                $entity = $dataMapper->findSingle( new Condition( "id", Condition::EqualTo, $row->id ) );

                if ( !is_null( $entity ) ) {
                    $entities[] = $entity;
                }

                continue;
            }

            $entity = $this->identity->newInstance();
            $this->mapFromRow( $entity, $row );
            $this->loadBaseClass( $entity );
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function loadBaseClass( Entity $entity ) {
        $baseClass = $this->identity->getBaseClass();

        if ( !is_null( $baseClass ) ) {
            $dataMapper = $this->unitOfWork->getDataMapper( $baseClass->getClassName() );
            $dataMapper->loadBaseClass( $entity );
        }

        if ( $this->identity->getClassName() === get_class( $entity ) ) {
            return;
        }

        $dbr = $this->unitOfWork->getDBR();
        $row = $dbr->selectRow(
            $this->identity->getTableName(),
            $dbr::ALL_ROWS, [
                ( new Condition( "id", Condition::EqualTo, $entity->getID() ) )->evaluate()
        ] );

        if ( !$row ) {
            throw new MWException( "No such row" );
        }

        $this->mapFromRow( $entity, $row );
    }

    public function insert( Entity $entity ) {
        $dbw = $this->unitOfWork->getDBW();
        $dbw->insert( $this->identity->getTableName(), $this->mapToRow( $entity ) );
        $this->identity->getProperty( "id" )->setValue( $entity, $dbw->insertId() );
    }

    public function update( Entity $entity ) {
        $dbw = $this->unitOfWork->getDBW();
        $dbw->update(
            $this->identity->getTableName(),
            $this->mapToRow( $entity ), [
                ( new Condition( "id", Condition::EqualTo, $entity->getID() ) )->evaluate()
        ] );
    }

    public function delete( Entity $entity ) {
        $dbw = $this->unitOfWork->getDBW();
        $dbw->delete(
            $this->identity->getTableName(), [
                ( new Condition( "id", Condition::EqualTo, $entity->getID() ) )->evaluate()
        ] );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function mapFromRow( Entity $entity, $row ) {
        $identifier = $this->identity->getProperty( "id" );
        $identifier->setValue( $entity, $row->id );
        $entity->markAsClean();

        $propertyNames = $this->identity->getPropertyNames();
        array_splice( $propertyNames, array_search( "id", $propertyNames ), 1 );

        while ( !empty( $propertyNames ) ) {
            $property = $this->identity->getProperty( array_pop( $propertyNames ) );

            if ( !$property->hasAssociation() ) {
                $columnName = $property->getColumnName();
                $property->setValue( $entity, $row->$columnName );
                continue;
            }

            $association = $property->getAssociation();
            $identity = $this->unitOfWork->getIdentity( $association->getTarget() );
            $dataMapper = $this->unitOfWork->getDataMapper( $association->getTarget() );

            switch ( get_class( $association ) ) {
                case OneToOneAssociation::class:
                    if ( is_null( $association->getMappedBy() ) ) {
                        $columnName = $property->getColumnName();

                        if ( is_null( $row->$columnName ) ) {
                            $property->setValue( $entity, null );

                            break;
                        }

                        $condition = new Condition( "id", Condition::EqualTo, $row->$columnName );
                    } else {
                        $columnName = $identity->getProperty( $association->getMappedBy() )->getColumnName();
                        $condition = new Condition( $columnName, Condition::EqualTo, $row->id );
                    }

                    $reference = $dataMapper->findSingle( $condition );
                    $property->setValue( $entity, $reference );
                    break;
                case OneToManyAssociation::class:
                    $columnName = $identity->getProperty( $association->getMappedBy() )->getColumnName();
                    $condition = new Condition( $columnName, Condition::EqualTo, $row->id );
                    $references = $dataMapper->findMultiple( $condition );
                    $property->setValue( $entity, $references );
                    break;
                case ManyToOneAssociation::class:
                    $columnName = $property->getColumnName();

                    if ( is_null( $row->$columnName ) ) {
                        $property->setValue( $entity, null );

                        break;
                    }

                    $condition = new Condition( "id", Condition::EqualTo, $row->$columnName );
                    $reference = $dataMapper->findSingle( $condition );
                    $property->setValue( $entity, $reference );
                    break;
            }
        }
    }

    public function mapToRow( Entity $entity ) : array {
        $row = [];

        $identifier = $this->identity->getProperty( "id" )->getValue( $entity );

        if ( !is_null( $identifier ) ) {
            $row["id"] = $identifier;
        }

        $propertyNames = $this->identity->getPropertyNames();
        array_splice( $propertyNames, array_search( "id", $propertyNames ), 1 );

        while ( !empty( $propertyNames ) ) {
            $property = $this->identity->getProperty( array_pop( $propertyNames ) );

            if ( is_null( $property->getColumnName() ) ) {
                continue;
            }

            $value = $property->getValue( $entity );

            if ( is_null( $value ) ) {
                continue;
            }

            if ( $property->hasAssociation() ) {
                $association = $property->getAssociation();

                if (
                    ( $association instanceof OneToOneAssociation && is_null( $association->getMappedBy() ) ) ||
                    $association instanceof ManyToOneAssociation
                ) {
                    $row[$property->getColumnName()] = $value->getID();
                }
            } else {
                $row[$property->getColumnName()] = $value;
            }
        }

        if ( $this->identity->hasSubClasses() ) {
            foreach ( $this->identity->getSubClassNames() as $subClassName ) {
                if ( $entity instanceof $subClassName ) {
                    $row["discriminator"] = $subClassName;
                }
            }
        }

        return $row;
    }

    /**
     * @throws MWException
     */
    public function generateID() : int {
        $dbr = $this->unitOfWork->getDBR();
        $row = $dbr->selectRow(
            "information_schema.tables", [
                "auto_increment"
        ], [
            ( new Conjunction() )
                ->add( new Condition( "table_schema", Condition::EqualTo, $dbr->getDBname() ) )
                ->add( new Condition( "table_name", Condition::EqualTo, $this->identity->getTableName() ) )
                ->evaluate()
        ] );

        if ( !$row ) {
            throw new MWException( "Can't generate ID" );
        }

        return $row->auto_increment;
    }
}