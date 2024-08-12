<?php

namespace MiniORM\Model\Property\Transform;

use MediaWiki\MediaWikiServices;
use Title;

class TitleTransformStrategy implements ITransformStrategy {
    /**
     * @param $value Title Title object.
     *
     * @return string Title text.
     */
    public function propertyToColumn( $value ) : string {
        return $value->getText();
    }

    /**
     * @param $value string Title text.
     *
     * @return Title Title object.
     */
    public function columnToProperty( $value ) : Title {
        return MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $value );
    }
}