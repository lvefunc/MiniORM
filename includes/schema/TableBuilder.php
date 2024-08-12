<?php

namespace MiniORM\Schema;

use MiniORM\Model\Association\ManyToOneAssociation;
use MiniORM\Model\Association\OneToOneAssociation;
use MiniORM\Model\Identity;
use MiniORM\UnitOfWork;
use MWException;
use ReflectionException;

class TableBuilder {
    private string $name;
    private array $columns = [];
    private array $pkConstraints = [];
    private array $fkConstraints = [];

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function setFromIdentity( Identity $identity ) : TableBuilder {
        $this->setName( $identity->getTableName() );
        $this->addPKConstraint( "id" );

        if ( !is_null( $identity->getBaseClass() ) ) {
            $this->addFKConstraint( "id", $identity->getBaseClass()->getTableName(), "id" );
        }

        if ( $identity->hasSubClasses() ) {
            $this->addColumn( "discriminator", "varbinary", 255, true, false );
        }

        foreach ( $identity->getPropertyNames() as $propertyName ) {
            $property = $identity->getProperty( $propertyName );

            if ( $property->isPersistent() ) {
                $this->addColumn(
                    $property->getColumnName(),
                    $property->getColumnType(),
                    $property->getColumnLength(),
                    $property->isColumnNullable(),
                    $property->getColumnName() === "id"
                );
            }

            if ( $property->hasAssociation() ) {
                $association = $property->getAssociation();

                if (
                    ( $association instanceof OneToOneAssociation && is_null( $association->getMappedBy() ) ) ||
                    $association instanceof ManyToOneAssociation
                ) {
                    $reference = UnitOfWork::getInstance()->getIdentity( $association->getTarget() );
                    $this->addFKConstraint( $property->getColumnName(), $reference->getTableName(), "id" );
                }
            }
        }

        return $this;
    }

    public function setName( string $name ) : TableBuilder {
        $this->name = $name;

        return $this;
    }

    public function addColumn( string $name, string $type, ?int $length, bool $isNullable, bool $isAutoIncrement ) : TableBuilder {
        $this->columns[] = [
            "name" => $name,
            "type" => $type,
            "length" => $length,
            "isNullable" => $isNullable,
            "isAutoIncrement" => $isAutoIncrement
        ];

        return $this;
    }

    public function addPKConstraint( string $column ) : TableBuilder {
        $this->pkConstraints[] = $column;

        return $this;
    }

    public function addFKConstraint( string $column, string $refTable, string $refColumn ) : TableBuilder {
        $this->fkConstraints[] = [
            "column" => $column,
            "refTable" => $refTable,
            "refColumn" => $refColumn
        ];

        return $this;
    }

    public function build() : string {
        $sql = "CREATE TABLE /*_*/" . $this->name . " (";

        for ( $i = 0; $i < count( $this->columns ); $i++ ) {
            $column = $this->columns[$i];
            $sql .= $i !== 0 ? "," : "";
            $sql .= "\n    " . $column["name"] . " " . $column["type"];
            $sql .= !is_null( $column["length"] ) ? ( "(" . $column["length"] . ")" ) : "";
            $sql .= " " . ( $column["isNullable"] ? "DEFAULT NULL" : "NOT NULL" );
            $sql .= $column["isAutoIncrement"] ? " auto_increment" : "";
        }

        $pkConstraint = "";

        for ( $i = 0; $i < count( $this->pkConstraints ); $i++ ) {
            $pkConstraint .= ( $i !== 0 ? ", " : "" ) . $this->pkConstraints[$i];
        }

        $sql .= !empty( $pkConstraint ) ? ",\n    CONSTRAINT PRIMARY KEY (" . $pkConstraint . ")" : "";

        for ( $i = 0; $i < count( $this->fkConstraints ); $i++ ) {
            $fkConstraint = $this->fkConstraints[$i];
            $sql .= ",\n    CONSTRAINT FOREIGN KEY (" . $fkConstraint["column"] . ")";
            $sql .= " REFERENCES " . $fkConstraint["refTable"] . " (" . $fkConstraint["refColumn"] . ")";
        }

        $sql .= "\n) /*\$wgDBTableOptions*/;";

        return $sql;
    }
}