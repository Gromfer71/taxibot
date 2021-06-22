<?php
namespace App\VendorExt;

use \Illuminate\Database\Query;
use \Illuminate\Database\Eloquent;

/**
 * Class Model
 * @package App\VendorExt
 *
 *
 * @property int $id
 *
 * @method static Query\Builder|Eloquent\Builder where (...$params)
 * @method static Query\Builder orWhere (...$params)
 * @method static Query\Builder|Eloquent\Builder select (...$params)
 * @method static Query\Builder whereRaw (...$params)
 * @method static Query\Builder whereIn (...$params)
 * @method static Query\Builder orWhereIn (...$params)
 * @method static Query\Builder whereLike (...$params)
 * @method static Query\Builder orderBy (...$params)
 * @method static Query\Builder take (...$params)
 * @method static Query\Builder whereNotNull (...$params)
 * @method static Query\Builder whereNull (...$params)
 * @method static Query\Builder join (...$params)
 * @method static Query\Builder leftJoin (...$params)
 * @method static Query\Builder whereHas ($params, $closure)
 * @method static $this find(int $id) get object
 */
class Model extends Eloquent\Model
{

//    public $timestamps = false;

    public static $__storage = [];
    protected $fillable = [];
    protected $guarded = [];

    //protected $guarded = array('*');

    /**
     * Используется для вывода ошибок
     * и для построения label
     *
     * @var array
     */
    public static $fieldName = [
        // 'name' => 'title',
        // ...
    ];

    /**
     * Используется для валидации
     *
     * @var array
     */
    public static $rules = [
        // 'name' => 'validation',
        // ...
    ];

    /**
     * Получить название поля
     *
     * @param $name
     * @return bool
     */
    public function getFieldName($name)
    {
        if (isset(static::$fieldName[$name])) {
            return static::$fieldName[$name];
        }

        return false;
    }

    public function isFieldRequired($name)
    {
        if (isset(static::$rules[$name])) {
            $rules = explode('|', static::$rules[$name]);
            return in_array('required', $rules);
        }

        return false;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            static::__setNullables($model);
        });
    }

    /**
     * @param $model self
     * @return bool
     */
    protected static function __setNullables($model)
    {
        foreach ($model->attributes as $name => $value) {
            if ($value === 'null' || $value === 'NULL' || is_null($value)) {
                $model->{$name} = null;
            }
        }

        return true;
    }

    protected function __getJsonAttribute($value)
    {
        if (!is_array($value)) {
            if (!$value = json_decode($value, 1))
                $value = [];
        }

        return $value;
    }

    protected function __prepareJsonAttribute($value)
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    protected function __setJsonAttribute($field, $value)
    {
        $this->attributes[$field] = $this->__prepareJsonAttribute($value);
    }

    /**
     * Encode the given value as JSON.
     *
     * @param  mixed $value
     * @return string
     */
    protected function asJson($value)
    {
        if (is_string($value))
            return $value;

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public static function checkForRules($data, $title = [])
    {
        /**
         * @var Model $class
         */
        $class = get_called_class();

        $rules = [];
        foreach ($data as $k => $v) {
            if (isset($class::$rules[$k])) {
                $rules[$k] = $class::$rules[$k];
            }

            if (!isset($title[$k]) && isset($class::$fieldName[$k])) {
                $title[$k] = $class::$fieldName[$k];
            }
        }

        if (count($rules) == 0)
            return true;

        $v = \Validator::make($data, $rules);
        $v->setAttributeNames($title);

        if ($v->fails()) {
            $messages = $v->errors();

            $error = [];
            foreach ($data as $k => $v) {
                if ($message = $messages->first($k)) {
                    $error[$k] = $message;
                }
            }

            return $error;
        } else {
            return true;
        }
    }

}