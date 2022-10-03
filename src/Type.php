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
        return is_int($type) && in_array($type, self::getScalarTypes());
    }

    public static function isValidPhone($value): bool
    {
        if (is_array($value)) {
            return false;
        }
        $value = str_replace(' ', '', $value);
        $match1 = preg_match("/^((\+7)|7|8)[0-9]{10}$/", $value);
        $match2 = preg_match("/^((\+7)|7|8)\([0-9]{3}\)[0-9]{3}\-[0-9]{2}\-[0-9]{2}$/", $value);
        return $match1 || $match2;
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
        if (Utils::isSequentialArray($type)
            && count($type) === 2
            && $type[0] === self::ARRAY
            && (
                self::isValidScalar($type[1]) ||
                (Utils::isSequentialArray($type[1]) && count($type[1]) === 2)
            )
        ) {
                return true;
        }

        return false;
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
     * Возвращает список всех типов в виде ассоциативного массива
     * [Имя_типа] => значение
     *
     * @return int[]
     */
    public static function getAll(): array
    {
        $oClass = new ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
    /**
     * Возвращает список скалярных типов в виде ассоциативного массива
     * [Имя_типа] => значение
     *
     * @return int[]
     */
    public static function getScalarTypes(): array
    {
        $all = self::getAll();
        do $scalars[] = current($all);
        while (next($all) !== self::MAP);
        return $scalars;
    }
}