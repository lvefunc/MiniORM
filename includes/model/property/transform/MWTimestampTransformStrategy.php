<?php

namespace MiniORM\Model\Property\Transform;

use MWTimestamp;
use Wikimedia\Timestamp\TimestampException;

class MWTimestampTransformStrategy implements ITransformStrategy {
    /**
     * @param $value MWTimestamp Timestamp object.
     *
     * @return string Timestamp as string.
     * @throws TimestampException
     */
    public function propertyToColumn( $value ) : string {
        return $value->getTimestamp();
    }

    /**
     * @param $value string Timestamp as string.
     *
     * @return MWTimestamp Timestamp object.
     */
    public function columnToProperty( $value ) : MWTimestamp {
        return MWTimestamp::getInstance( $value );
    }
}