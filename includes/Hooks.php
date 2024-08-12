<?php

namespace MiniORM;

use DatabaseUpdater;
use MiniORM\Annotation\AnnotationRegistry;
use MiniORM\Annotation\BaseClass;
use MiniORM\Annotation\Column;
use MiniORM\Annotation\ManyToOne;
use MiniORM\Annotation\OneToMany;
use MiniORM\Annotation\OneToOne;
use MiniORM\Annotation\Table;
use MiniORM\Model\Property\Transform\MWTimestampTransformStrategy;
use MiniORM\Model\Property\Transform\TitleTransformStrategy;
use MiniORM\Model\Property\Transform\TransformStrategyRegistry;
use MiniORM\Model\Property\Transform\UserTransformStrategy;
use MiniORM\Schema\SchemaUpdater;
use MWException;
use MWTimestamp;
use ReflectionException;
use Title;
use User;

class Hooks {
    public static function registerAnnotations( AnnotationRegistry $annotationRegistry ) {
        $annotationRegistry->register( "Column", Column::class );
        $annotationRegistry->register( "Table", Table::class );
        $annotationRegistry->register( "BaseEntity", BaseClass::class );
        $annotationRegistry->register( "OneToOne", OneToOne::class );
        $annotationRegistry->register( "OneToMany", OneToMany::class );
        $annotationRegistry->register( "ManyToOne", ManyToOne::class );
    }

    public static function registerTransformStrategies( TransformStrategyRegistry $transformStrategyRegistry ) {
        $transformStrategyRegistry->register( User::class, UserTransformStrategy::class );
        $transformStrategyRegistry->register( Title::class, TitleTransformStrategy::class );
        $transformStrategyRegistry->register( MWTimestamp::class, MWTimestampTransformStrategy::class );
    }

    /**
     * @throws ReflectionException
     * @throws MWException
     */
    public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $databaseUpdater ) {
        $schemaUpdater = SchemaUpdater::getInstance();
        $schemaUpdater->generateSQLFiles();

        foreach ( $schemaUpdater->getUpdateList() as $table ) {
            $databaseUpdater->addExtensionTable( $table, sys_get_temp_dir() . "/add-" . $table . ".sql" );
        }
    }
}
