<?php

namespace MiniORM\Annotation;

use MWException;

class AnnotationParser {
    private static ?AnnotationParser $instance = null;

    public static function getInstance() : AnnotationParser {
        if ( self::$instance === null ) {
            self::$instance = new AnnotationParser();
        }

        return self::$instance;
    }

    private const key = "[A-Za-z]+";
    private const boolean = "true|false";
    private const integer = "-?[1-9][0-9]*";
    private const string = "\"[A-Za-z0-9\\\\\\-_]*\"";

    private const annotation =
        "/(?(DEFINE)" .
        "(?<key>(?>" . self::key . "))" .
        "(?<boolean>(?>" . self::boolean . "))" .
        "(?<integer>(?>" . self::integer . "))" .
        "(?<string>(?>" . self::string . "))" .
        "(?<value>(?>(?&boolean)|(?&integer)|(?&string)))" .
        "(?<pair>(?>(?&key)\s*:\s*(?&value)))" .
        "(?<array>(?>\(\s*(?>(?&pair)(?>\s*,\s*(?&pair))*)?\s*\)))" .
        ")" .
        "@(?<annotation>(?>(?&key)))\s*(?<attributes>(?&array))?/x";

    private const attributes =
        "/(?<key>(?>" . self::key . "))\s*:\s*(?<value>(?>(?<boolean>(?>" . self::boolean . "))|(?<integer>(?>" . self::integer . "))|(?<string>(?>" . self::string . "))))/m";

    /**
     * @param string $docComment
     *
     * @return IAnnotation[]
     * @throws MWException
     */
    public function match( string $docComment ) : array {
        $docComment = str_replace( [ "/**", "/*", "*", "\r\n", "\r", "\n" ], "", $docComment );
        preg_match_all( self::annotation, $docComment, $matches, PREG_SET_ORDER );

        if ( empty( $matches ) ) {
            return [];
        }

        $annotations = [];

        foreach ( $matches as $match ) {
            $annotation = $match["annotation"];
            $annotationClass = AnnotationRegistry::getInstance()->getAnnotationClass( $annotation );

            if ( is_null( $annotationClass ) ) {
                continue;
            }

            $attributes = $match["attributes"];
            $annotations[] = new $annotationClass( $this->matchAttributes( $attributes ) );
        }

        return $annotations;
    }

    /**
     * @throws MWException
     */
    public function matchAttributes( string $attributes ) : array {
        preg_match_all( self::attributes, $attributes, $matches, PREG_SET_ORDER );

        if ( empty( $matches ) ) {
            return [];
        }

        $attributes = [];

        foreach ( $matches as $match ) {
            $value = $match["value"];

            if ( $value === $match["boolean"] ) {
                $attributes[$match["key"]] = $value === "true";
            } else if ( $value === $match["integer"] ) {
                $attributes[$match["key"]] = intval( $value );
            } else if ( $value === $match["string"] ) {
                $attributes[$match["key"]] = substr( $value, 1, ( strlen( $value ) - 2 ) );
            } else {
                throw new MWException( "Unrecognized value type" );
            }
        }

        return $attributes;
    }
}