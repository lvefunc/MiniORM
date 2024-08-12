<?php

namespace MiniORM\Schema;

use MediaWiki\MediaWikiServices;
use MiniORM\Model\Identity;
use MiniORM\UnitOfWork;
use MWException;
use ReflectionException;

class SchemaUpdater {
    private static ?SchemaUpdater $instance = null;

    public static function getInstance() : SchemaUpdater {
        if ( self::$instance === null ) {
            self::$instance = new SchemaUpdater();
        }

        return self::$instance;
    }

    private array $registry = [];

    public function __construct() {
        MediaWikiServices::getInstance()->getHookContainer()->run( "RegisterSchemaUpdates", [ &$this ] );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function register( string $class ) {
        $identity = UnitOfWork::getInstance()->getIdentity( $class );

        if ( !in_array( $identity, $this->registry ) ) {
            $this->registry[] = $identity;
        }
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function generateSQLFiles() {
        foreach ( $this->registry as $identity ) {
            $tableBuilder = new TableBuilder();
            $tableBuilder->setFromIdentity( $identity );
            $sqlText = $tableBuilder->build();

            file_put_contents( sys_get_temp_dir() . "/add-" . $identity->getTableName() . ".sql", $sqlText );
        }
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public function getUpdateList() : array {
        $commitOrder = UnitOfWork::getInstance()->getCommitOrder( $this->registry );
        $tables = [];

        foreach ( $commitOrder as $entry ) {
            $identity = UnitOfWork::getInstance()->getIdentity( $entry );

            if ( in_array( $identity, $this->registry ) ) {
                $tables[] = $identity->getTableName();
            }
        }

        return $tables;
    }
}
