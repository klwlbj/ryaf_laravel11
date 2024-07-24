<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @method static Collection        findMany($id, $columns = ['*'])
 * @method static static|Collection find($id, $columns = ['*'])
 * @method static static|Collection findOrFail($id, $columns = ['*'])
 * @method static static|Collection findOrError($id, $errorMessage = '')
 * @method static static            findOrNew($id, $columns = ['*'])
 * @method static static            firstOrNew(array $attributes, array $values = [])
 * @method static static            firstOrCreate(array $attributes, array $values = [])
 * @method static static            updateOrCreate(array $attributes, array $values = [])
 * @method static static            first($columns = ['*'])
 * @method static static            firstOrFail($columns = ['*'])
 *
 */
class BaseModel extends Model
{
    public const NO  = 0;
    public const YES = 1;

    public static $yesOrNo = [
        self::NO  => '否',
        self::YES => '是',
    ];

    public const STATUS_NORMAL = 0;
    public const STATUS_BAN    = 1;

    public static $statusMaps = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_BAN    => '禁用',
    ];

    protected static function boot()
    {
        parent::boot();

        static::eventBoot();

        static::baseModelBoot();
    }

    protected static function eventBoot()
    {
    }

    protected static function baseModelBoot()
    {
    }

    /**
     * @return array
     */
    protected static function getDefaultDict(): array
    {
        return static::$yesOrNo;
    }

    /**
     * 批量插入,防止占位符溢出
     *
     * @param array|Collection $records
     * @param integer $limit
     * @return void
     */
    public static function safeInsert(array|Collection $records, int $limit = 500)
    {
        if ($records instanceof Collection) {
            $records = $records->toArray();
        }

        $offset = 0;
        while (true) {
            $data = array_slice($records, $offset, $limit);

            if (empty($data)) {
                break;
            }

            static::insert($data);

            $offset += $limit;
        }
    }

}
