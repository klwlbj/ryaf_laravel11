<?php

namespace App\Http\Logic;

class BaseLogic
{
    protected $time = null;
    /**
     * @var static[] static instances in format: `[className => object]`
     */
    private static $models;

    /**
     * Returns static class instance, which can be used to obtain meta information.
     * @param array $config
     * @param bool $refresh whether to re-create static instance even, if it is already cached.
     * @return static class instance.
     */
    public static function getInstance()
    {
        $name = get_called_class();
        if(!isset(self::$models[$name])) {
            self::$models[$name] = new $name();
        }
        return self::$models[$name];
    }

    public function __construct()
    {
        $this->time = time();
    }
}
