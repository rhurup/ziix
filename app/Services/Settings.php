<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class Settings
{

    public static $settings_file = '/settings.json';

    public static function set($key, $value)
    {

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (is_bool($bool) && $bool !== NULL) {
            $value = $bool;
        }

        $settings = json_decode(self::load());
        $settings->{$key} = $value;

        File::put(storage_path(self::$settings_file), json_encode($settings));

        return $value;
    }

    private static function load()
    {
        return File::get(storage_path(self::$settings_file));
    }

    public static function get($key, $default = 0)
    {

        $settings = json_decode(self::load());

        if (!property_exists($settings, $key)) {
            return $default;
        }

        return $settings->{$key};
    }

    public static function all()
    {

        $settings = json_decode(self::load());

        return $settings;
    }

}
