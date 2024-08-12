<?php

namespace MiniORM\Model\Association;

use MiniORM\Annotation\Association;
use MiniORM\Annotation\ManyToOne;
use MiniORM\Annotation\OneToMany;
use MiniORM\Annotation\OneToOne;
use MWException;

class AssociationBuilder {
    public const OneToOne = 0;
    public const OneToMany = 1;
    public const ManyToOne = 2;

    private int $type;
    private string $target;
    private ?string $mappedBy = null;

    public function __construct() {
    }

    public function setFromAnnotation( Association $annotation ) : AssociationBuilder {
        $this->setTarget( $annotation->getTarget() );

        switch ( get_class( $annotation ) ) {
            case OneToOne::class:
                $this->setType( self::OneToOne );
                $this->setMappedBy( $annotation->getMappedBy() );
                break;
            case OneToMany::class:
                $this->setType( self::OneToMany );
                $this->setMappedBy( $annotation->getMappedBy() );
                break;
            case ManyToOne::class:
                $this->setType( self::ManyToOne );
                break;
        }

        return $this;
    }

    public function getType() : int {
        return $this->type;
    }

    public function setType( int $type ) : AssociationBuilder {
        $this->type = $type;

        return $this;
    }

    public function getTarget() : string {
        return $this->target;
    }

    public function setTarget( string $target ) : AssociationBuilder {
        $this->target = $target;

        return $this;
    }

    public function getMappedBy() : ?string {
        return $this->mappedBy;
    }

    public function setMappedBy( ?string $mappedBy ) : AssociationBuilder {
        $this->mappedBy = $mappedBy;

        return $this;
    }

    /**
     * @throws MWException
     */
    public function build() {
        switch ( $this->type ) {
            case self::OneToOne:
                return new OneToOneAssociation( $this );
            case self::OneToMany:
                return new OneToManyAssociation( $this );
            case self::ManyToOne:
                return new ManyToOneAssociation( $this );
            default:
                throw new MWException( "Unsupported association type" );
        }
    }
}