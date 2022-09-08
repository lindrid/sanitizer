# Санитайзер
Библиотека "санитайзер" занимается валидацией и нормализацией json данных в соответствии с переданной спецификацией типов.

## Требования
php 7.4

## Установка
composer install lindrid/sanitizer

## Использование
$s = new Sanitizer('{"foo": "123", "bar": "asd", "baz": "8 (950) 288-56-23", "arr": [1, 2], "map": {"a": 1.5}',
    ['foo' => Type::INT, 'bar' => Type::STRING, 'baz' => Type::PHONE, 'arr' => [Type::ARRAY, Type::INT],
        'map' => [Type::MAP, ['a' => Type::FLOAT]]]
);

$fields = $s->getFields();

// $fields['foo'] === 123; // true
// $fields['bar'] === "asd"; // true
// $fields['baz'] === "79502885623"; // true
// $fields['arr'] === [1,2]; // true
// $fields['map'] === ['a' => 1.5]; //true