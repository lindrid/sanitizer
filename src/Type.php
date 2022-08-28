<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer;

use ReflectionClass;

/**
 * Класс описывает два типа полей:
 *      1) скалярный
 *      2) вложенный
 */
class Type
{
    const INT = 0;
    const FLOAT = 1;
    const STRING = 2;
    const PHONE = 3;

    // при добавлении типа вносим правки в isValid

    /**
     * тип элемента мэпа может быть любым
     */
    const MAP = 100;
    /**
     * все элементы массива имеют одинаковый тип
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
        return in_array($type, range(0, 3));
    }

    /**
     * @param mixed $type
     * @return bool
     */
    public static function isValidNested($type): bool
    {
        if (is_array($type) && count($type) === 2) {
            $nestedType = $type[0];
            $nestedStructDefinition = $type[1];
            if ($nestedType === self::MAP) {
                if (Utils::isAssociativeArray($nestedStructDefinition)) {
                    foreach ($nestedStructDefinition as $mapKey => $mapValue) {
                    }
                    return true;
                }
                return false;
            }
            if ($nestedType === self::ARRAY) {
                if (Utils::isSequentialArray($type[1])) {
                    return empty($type[1]) || self::isValidNested($type[1]);
                }
                return self::isValidScalar($type[1]);
            }
        }

        return false;
    }

    /**
     * Метод проверяет является ли тип одним из вложенных типов (map, array)
     *
     * @param int $type
     * @return bool
     */
    public static function isNestedType(int $type): bool
    {
        return in_array($type, [self::MAP, self::ARRAY]);
    }

    /**
     * Имя типа большими буквами
     *
     * @param int $type
     * @return string
     */
    public static function getName(int $type): string
    {
        return array_flip(self::getConstants())[$type];
    }

    private static function getConstants(): array
    {
        $oClass = new ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}