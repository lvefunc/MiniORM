<?php

namespace MiniORM\Model\Association;

class OneToOneAssociation extends Association {
    private ?string $mappedBy;

    public function __construct( AssociationBuilder $associationBuilder ) {
        parent::__construct( $associationBuilder );
        $this->mappedBy = $associationBuilder->getMappedBy();
    }

    public function getMappedBy() : ?string {
        return $this->mappedBy;
    }
}