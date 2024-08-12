<?php

namespace MiniORM\Model\Property\Transform;

use MediaWiki\MediaWikiServices;

class TransformStrategyRegistry {
    private static ?TransformStrategyRegistry $instance = null;

    public static function getInstance() : TransformStrategyRegistry {
        if ( self::$instance === null ) {
            self::$instance = new TransformStrategyRegistry();
        }

        return self::$instance;
    }

    private array $registry = [];

    private function __construct() {
        MediaWikiServices::getInstance()->getHookContainer()->run( "RegisterTransformStrategies", [ &$this ] );
    }

    public function register( string $propertyTypeName, string $transformStrategyClass ) {
        $this->registry[$propertyTypeName] = $transformStrategyClass;
    }

    public function getTransformStrategy( string $propertyTypeName ) : ?ITransformStrategy {
        if ( !isset( $this->registry[$propertyTypeName] ) ) {
            return new DefaultTransformStrategy();
        } else {
            $transformStrategyClass = $this->registry[$propertyTypeName];

            return new $transformStrategyClass();
        }
    }
}