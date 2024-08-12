<?php

namespace MiniORM\Model\Property\Transform;

use MediaWiki\MediaWikiServices;
use User;

class UserTransformStrategy implements ITransformStrategy {
    /**
     * @param $value User User object.
     *
     * @return int User id.
     */
    public function propertyToColumn( $value ) : int {
        return $value->getId();
    }

    /**
     * @param $value int User id.
     *
     * @return User User object.
     */
    public function columnToProperty( $value ) : User {
        return MediaWikiServices::getInstance()->getUserFactory()->newFromId( $value );
    }
}