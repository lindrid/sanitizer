<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer;

use ReflectionClass;

/**
 * Класс описывает два типа полей:
 *      1) скалярный
 *      2) составной (вложенный)
 */
class Type
{
    const INT = 0;
    const FLOAT = 1;
    const STRING = 2;
    const PHONE = 3;

    // при добавлении скалярного типа, вносим правки в isValidScalar()
    // при добавлении составного типа, вносим правки в getNested()

    /**
     * тип элемента мэпа может быть любым
     */
    const MAP = 100;
    /**
     * все элементы массива должны иметь одинаковый тип
     */
    const ARRAY = 101;

    /**
     * Метод проверяет является ли тип поля валидным скалярным типом.
     *
     * @param mixed $type
     * @return bool
     */
    public static function isValidScalar($type): bool
    {
        return is_int($type) && in_array($type, range(0, 3));
    }

    /**
     * @param mixed $type
     * @return bool
     */
    public static function isValidMap($type): bool
    {
        if (is_array($type)
            && count($type) === 2
            && $type[0] === self::MAP
            && Utils::isAssociativeArray($type[1])
        ) {
            return true;
        }

        return false;
    }

    public static function isValidArray($type)
    {
        if (is_array($type)
            && count($type) === 2
            && $type[0] === self::ARRAY
            && (self::isValidScalar($type[1]) || Utils::isAssociativeArray($type[1]))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Метод проверяет является ли тип одним из вложенных типов (map, array)
     *
     * @param $type
     * @return bool
     */
    public static function isNestedType($type): bool
    {
        return is_int($type) && in_array($type, self::getNested());
    }

    /**
     * Имя типа заглавными буквами
     *
     * @param int $type
     * @return string
     */
    public static function getName(int $type): string
    {
        return array_flip(self::getAll())[$type];
    }

    /**
     * Возвращает список всех типов в виде целочисленных значений
     *
     * @return int[]
     */
    public static function getAll(): array
    {
        $oClass = new ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

    /**
     * Возвращает список всех составных типов в виде целочисленных значений
     *
     * @return int[]
     */
    public static function getNested(): array
    {
        return [self::MAP, self::ARRAY];
    }
}