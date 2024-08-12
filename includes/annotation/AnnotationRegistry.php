<?php

namespace MiniORM\Annotation;

use MediaWiki\MediaWikiServices;

class AnnotationRegistry {
    private static ?AnnotationRegistry $instance = null;

    public static function getInstance() : AnnotationRegistry {
        if ( self::$instance === null ) {
            self::$instance = new AnnotationRegistry();
        }

        return self::$instance;
    }

    private array $registry = [];

    private function __construct() {
        MediaWikiServices::getInstance()->getHookContainer()->run( "RegisterAnnotations", [ &$this ] );
    }

    public function register( string $annotationName, string $annotationClass ) {
        $this->registry[$annotationName] = $annotationClass;
    }

    public function getAnnotationName( string $annotationClass ) {
        return array_flip( $this->registry )[$annotationClass] ?? null;
    }

    public function getAnnotationClass( string $annotationName ) {
        return $this->registry[$annotationName] ?? null;
    }
}