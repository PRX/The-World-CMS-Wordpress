<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Datetime_Field extends PMXE_Addon_Field {

    public function isTimestamp( $string ) {
        try {
            new \DateTime( '@' . $string );
        } catch ( \Exception $e ) {
            return false;
        }

        return true;
    }

    public function toString() {
        if ( ! $this->value ) {
            return '';
        }

        $is_timestamp = $this->isTimestamp( $this->value );
        $timestamp    = $is_timestamp ? $this->value : strtotime( $this->value );

        return prepare_date_field_value(
            $this->settings,
            $timestamp,
            "Y-m-d H:i:s",
            ! $is_timestamp
        );
    }
}
