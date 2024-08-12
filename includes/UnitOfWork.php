<?php

namespace MiniORM;

use IDatabase;
use MediaWiki\MediaWikiServices;
use MiniORM\Expression\Condition;
use MiniORM\Expression\IExpression;
use MiniORM\Model\Association\ManyToOneAssociation;
use MiniORM\Model\Association\OneToOneAssociation;
use MiniORM\Model\Identity;
use MiniORM\Model\IdentityBuilder;
use MWException;
use ReflectionException;

class UnitOfWork {
    private static ?UnitOfWork $instance = null;

    public static function getInstance() : UnitOfWork {
        if ( self::$instance === null ) {
            self::$instance = new UnitOfWork();
        }

        return self::$instance;
    }

    private bool $debug = false;

    private IDatabase $dbr;
    private IDatabase $dbw;

    private array $identities = [];
    private array $dataMappers = [];

    private array $clean = [];
    private array $new = [];
    private array $dirty = [];
    private array $removed = [];

    private function __construct() {
    }

    public function isDebugMode() : bool {
        return $this->debug;
    }

    public function setDebugMode( bool $debug ) {
        $this->debug = $debug;
    }

    public function getDBR() : IDatabase {
        if ( !isset( $this->dbr ) ) {
            $this->dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
        }

        return $this->dbr;
    }

    public function getDBW() : IDatabase {
        if ( !isset( $this->dbw ) ) {
            $this->dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
        }

        return $this->dbw;
    }

    /**
     * @param object|string $objectOrClass
     *
     * @throws ReflectionException
     * @throws MWException
     */
    public function getIdentity( $objectOrClass ) {
        $className = is_object( $objectOrClass ) ? get_class( $objectOrClass ) : $objectOrClass;

        if ( !isset( $this->identities[$className] ) ) {
            $this->identities[$className] = ( new IdentityBuilder( $this, $className ) )->build();
        }

        return $this->identities[$className];
    }

    /**
     * @param object|string $objectOrClass
     *
     * @throws ReflectionException
     * @throws MWException
     */
    public function getDataMapper( $objectOrClass ) {
        $className = is_object( $objectOrClass ) ? get_class( $objectOrClass ) : $objectOrClass;

        if ( !isset( $this->dataMappers[$className] ) ) {
            $this->dataMappers[$className] = new DataMapper( $this, $this->getIdentity( $className ) );
        }

        return $this->dataMappers[$className];
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function count( string $className, ?IExpression $expression = null ) : int {
        return $this->getDataMapper( $className )->count( $expression );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function findByID( string $className, int $id ) : ?Entity {
        $condition = new Condition( "id", Condition::EqualTo, $id );

        return $this->getDataMapper( $className )->findSingle( $condition );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function findSingle( string $className, IExpression $expression ) : ?Entity {
        return $this->getDataMapper( $className )->findSingle( $expression );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function findMultiple( string $className, IExpression $expression, array $options = [] ) : array {
        return $this->getDataMapper( $className )->findMultiple( $expression, $options );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function commit() {
        $entities = array_merge( $this->new, $this->dirty, $this->removed );
        $identities = [];

        foreach ( $entities as $entity ) {
            $identity = $this->getIdentity( $entity );

            if ( !in_array( $identity, $identities ) ) {
                $identities[] = $identity;
            }
        }

        $commitOrder = $this->getCommitOrder( $identities );

        foreach ( $commitOrder as $className ) {
            $this->executeInserts( $className );
        }

        foreach ( $commitOrder as $className ) {
            $this->executeUpdates( $className );
        }

        foreach ( array_reverse( $commitOrder ) as $className ) {
            $this->executeDeletions( $className );
        }

        $this->doCleanup();
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function executeInserts( string $className ) {
        foreach ( $this->new as $new ) {
            if ( $new instanceof $className ) {
                $this->getDataMapper( $className )->insert( $new );
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function executeUpdates( string $className ) {
        foreach ( $this->dirty as $dirty ) {
            if ( $dirty instanceof $className ) {
                $this->getDataMapper( $className )->update( $dirty );
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function executeDeletions( string $className ) {
        foreach ( $this->removed as $removed ) {
            if ( $removed instanceof $className ) {
                $this->getDataMapper( $className )->delete( $removed );
            }
        }
    }

    /**
     * @param Identity[] $identities
     *
     * @return string[]
     * @throws ReflectionException
     * @throws MWException
     */
    public function getCommitOrder( array $identities ) : array {
        $commitOrderCalculator = new CommitOrderCalculator();
        $queue = $identities;

        while ( !empty( $queue ) ) {
            $identity = array_pop( $queue );

            if ( !$commitOrderCalculator->addNode( $identity->getClassName() ) ) {
                continue;
            }

            $baseClass = $identity->getBaseClass();

            if ( !is_null( $baseClass ) ) {
                $commitOrderCalculator->addDependency( $identity->getClassName(), $baseClass->getClassName() );
                array_push( $queue, $baseClass );
            }

            foreach ( $identity->getPropertyNames() as $propertyName ) {
                $property = $identity->getProperty( $propertyName );

                if ( $property->hasAssociation() ) {
                    $association = $property->getAssociation();

                    if (
                        ( $association instanceof OneToOneAssociation && is_null( $association->getMappedBy() ) ) ||
                        $association instanceof ManyToOneAssociation
                    ) {
                        $reference = $this->getIdentity( $association->getTarget() );
                        $commitOrderCalculator->addDependency( $identity->getClassName(), $reference->getClassName() );
                        array_push( $queue, $reference );
                    }
                }
            }
        }

        return $commitOrderCalculator->calculate();
    }

    /**
     * @param string $className
     * @param int $id
     *
     * @return Entity|null
     * @throws ReflectionException
     * @throws MWException
     */
    public function getCached( string $className, int $id ) : ?Entity {
        $rootClass = $this->getIdentity( $className )->getRootClass();

        return $this->clean[$rootClass->getClassName()][$id] ?? null;
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function registerClean( Entity $entity ) {
        $rootClass = $this->getIdentity( $entity )->getRootClass()->getClassName();
        $id = $entity->getID();

        if ( !isset( $this->clean[$rootClass] ) ) {
            $this->clean[$rootClass] = [];
        }

        if ( !isset( $this->clean[$rootClass][$id] ) ) {
            $this->clean[$rootClass][$id] = $entity;
        }
    }

    public function registerNew( Entity $entity ) {
        $this->new[$entity->getHash()] = $entity;
    }

    public function registerDirty( Entity $entity ) {
        if ( !isset( $this->new[$entity->getHash()] ) ) {
            $this->dirty[$entity->getHash()] = $entity;
        }
    }

    public function registerRemoved( Entity $entity ) {
        $this->removed[$entity->getHash()] = $entity;
    }

    public function doCleanup() {
        $this->clean = [];
        $this->new = [];
        $this->dirty = [];
        $this->removed = [];
    }
}