<?php

namespace MiniORM;

use Exception;
use MWException;
use ReflectionException;

abstract class Entity {
    protected int $id;
    protected string $hash;

    /**
     * @throws Exception
     */
    public function __construct() {
        $this->hash = bin2hex( random_bytes( 20 ) );
        $this->markAsNew();
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function markAsClean() {
        UnitOfWork::getInstance()->registerClean( $this );
    }

    public function markAsNew() {
        UnitOfWork::getInstance()->registerNew( $this );
    }

    public function markAsDirty() {
        UnitOfWork::getInstance()->registerDirty( $this );
    }

    public function markAsRemoved() {
        UnitOfWork::getInstance()->registerRemoved( $this );
    }

    public function equals( Entity $other ) : bool {
        return ( get_class( $this ) === get_class( $other ) ) && ( $this->getHash() === $other->getHash() );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function serialize() : array {
        return UnitOfWork::getInstance()->getIdentity( $this )->serialize( $this );
    }

    /**
     * @return int|null
     */
    public function getID() : ?int {
        return $this->id ?? null;
    }

    /**
     * @return string
     */
    public function getHash() : string {
        return $this->hash;
    }
}