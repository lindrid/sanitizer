<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer\Result\Messages;

class Error
{
    const FIELD_TYPE_NOT_SET = 'Тип поля %s не задан!';
    const INVALID_SCALAR_FIELD_TYPE = 'Поле %s задано несуществующим скалярным типом %s. Используйте константы Type::';
    const INVALID_NESTED_FIELD_TYPE = 'Поле %s задано несуществующим составным типом %s. Бывают 2 составных типа: '
        . 'Type::MAP, Type::ARRAY. Примеры корректной записи: [Type::MAP, ["first" => Type::INT, "second" => Type::FLOAT]]'
        . ', [Type::ARRAY, Type::INT]. Вместо скалярного Type::X, можно использовать вложенный [Type::MAP, [...]] или '
        . '[Type::ARRAY, Type::Y]';
    const INVALID_FIELD_VALUE = 'Значение %s поля "%s" невозможно преобразовать к типу Type::%s!';
    const INVALID_MAP_FIELD_TYPE = 'Тип MAP поля %s имеет некорректное описание %s! ' .
        'Пример корректной записи: [Type::MAP, ["first" => Type::INT, "second" => Type::FLOAT]]. Вместо скалярного '
            . 'Type::X, можно использовать вложенный [Type::MAP, [...]] или [Type::ARRAY, Type::Y]';
    const INVALID_ARRAY_FIELD_TYPE = 'Тип ARRAY поля %s имеет некорректное описание %s! ' .
        'Пример корректной записи: [Type::ARRAY, Type::INT]. Вместо скалярного Type::INT, можно использовать '
            . 'вложенный [Type::MAP, ["first" => Type::INT, "second" => Type::FLOAT]] или [Type::ARRAY, Type::Y]';
    const ARRAY_ELEMENTS_TYPE_NOT_SET = 'Тип %s элементов массива %s не задан!';
    const INVALID_ARRAY_ELEMENT_VALUE = 'Значение %s массива "%s" невозможно преобразовать к Type::%s!';
}