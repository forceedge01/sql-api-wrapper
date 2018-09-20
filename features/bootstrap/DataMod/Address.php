<?php

namespace DataMod;

use Genesis\SQLExtensionWrapper\BaseProvider;
use Genesis\SQLExtensionWrapper\DataModInterface;

/**
 * Address class.
 */
class Address extends BaseProvider implements DataModInterface
{
    /**
     * @return string
     */
    public static function getBaseTable()
    {
        return 'Address';
    }

    /**
     * @return array
     */
    public static function getDataMapping()
    {
        return [
            'id' => 'id',
            'user_id' => 'user_id',
            'address' => 'address',
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getDefaults(array $data = [])
    {
        return [
            'user_id' => User::getRequiredValue('id')
        ];
    }

    /**
     * @param array $where
     *
     * @return void
     */
    public static function delete(array $where = [])
    {
        if (! $where) {
            $where = [
                'id' => '!NULL'
            ];
        }

        parent::delete($where);
    }
}