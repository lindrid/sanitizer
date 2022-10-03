<?php

require '../vendor/autoload.php';
use Lindrid\Sanitizer\Exceptions\SanitizerException;
use Lindrid\Sanitizer\Result\Codes\Warning as WarningCode;
use Lindrid\Sanitizer\Result\Codes\Error as ErrorCode;
use Lindrid\Sanitizer\Result\IssueType;
use Lindrid\Sanitizer\Sanitizer;
use Lindrid\Sanitizer\Type;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    /**
     * @throws SanitizerException
     * @throws JsonException
     */
    public function testInstantiationOfSanitizer()
    {
        $s = new Sanitizer('{}', []);
        $this->assertInstanceOf('Lindrid\Sanitizer\Sanitizer', $s);
    }

    public function testNormalizeScalars()
    {
        $s = new Sanitizer('
            {
                "int1": "1", 
                "int2": 2.0,
                "int3": "3.0",
                "int4": 10e-1,
                "int5": "1e1",
                "f1": 1.5,
                "f2": 2.5e2,
                "f3": "0.0000000000001",
                "f4": "-3.9999e-10",
                "str": 1,
                "str2": 2.5,
                "str3": "this is a string",
                "phone": "8 (950) 288-56-23",
                "phone2": "+7 950 288 56 23",
                "phone3": 89502885623,
                "phone4": 79502885623
            }',
            [
                'int1' => Type::INT,
                'int2' => Type::INT,
                'int3' => Type::INT,
                'int4' => Type::INT,
                'int5' => Type::INT,
                'f1' => Type::FLOAT,
                'f2' => Type::FLOAT,
                'f3' => Type::FLOAT,
                'f4' => Type::FLOAT,
                'str' => Type::STRING,
                'str2' => Type::STRING,
                'str3' => Type::STRING,
                'phone' => Type::PHONE,
                'phone2' => Type::PHONE,
                'phone3' => Type::PHONE,
                'phone4' => Type::PHONE,
            ]
        );
        $this->assertSame(
            [
                'int1' => 1,
                'int2' => 2,
                'int3' => 3,
                'int4' => 1,
                'int5' => 10,
                'f1' => 1.5,
                'f2' => 250.0,
                'f3' => 0.0000000000001,
                'f4' => -3.9999e-10,
                'str' => '1',
                'str2' => '2.5',
                'str3' => 'this is a string',
                'phone' => '79502885623',
                'phone2' => '79502885623',
                'phone3' => '79502885623',
                'phone4' => '79502885623',
            ],
            $s->getFields()
        );
    }

    public function testNormalizeMaps()
    {
        $s = new Sanitizer('
            {
                "map": {"a": 1.0e1, "b": 2, "c": 1.5, "p":"+7 (950) 288-56-23"},
                "deep_map": {"m1":{"m2":{"i":"10.0", "p1":"+79502885623", "p2":89502885623, "p3":79502885623}}}
            }',
            [
                'map' => [Type::MAP, ['a' => Type::INT, 'b' => Type::FLOAT, 'c' => Type::STRING, 'p' => Type::PHONE]],
                'deep_map' => [Type::MAP, ['m1' => [Type::MAP, ['m2' => [Type::MAP,
                    ['i' => Type::INT, 'p1' => Type::PHONE, 'p2' => Type::PHONE, 'p3' => Type::PHONE]]]]]],
            ]
        );
        $this->assertSame(
            [
                'map' => ['a' => 10, 'b' => 2.0, 'c' => "1.5", 'p' => '79502885623'],
                'deep_map' => ['m1' => ['m2' =>
                    ['i' => 10, 'p1' => '79502885623', 'p2' => '79502885623', 'p3' => '79502885623']]],
            ],
            $s->getFields()
        );
    }

    public function testNormalizeArrays()
    {
        $s = new Sanitizer('
            {
                "array": ["-10e-1", "10.0", "2.0e1"],
                "deep_array": [[1e0,"2.0e0"], ["3",4.0], [-5.0,6.0e0], [7,8]],
                "array_of_map": [
                    [{"i":1,"f":0.5e0}, {"i":2.0,"f":"1.5e0"}], 
                    [{"i":"3","f":"2"}, {"i":"4.0","f":"3.0000005"}]
                ]
            }',
            [
                'array' => [Type::ARRAY, Type::INT],
                'deep_array' => [Type::ARRAY, [Type::ARRAY, Type::INT]],
                'array_of_map' => [Type::ARRAY, [Type::ARRAY, [Type::MAP,
                    ['i' => Type::INT, 'f' => Type::FLOAT]]]]
            ]
        );
        $this->assertSame(
            [
                'array' => [-1, 10, 20],
                'deep_array' => [[1,2], [3,4], [-5,6], [7,8]],
                'array_of_map' => [
                    [['i' => 1, 'f' => 0.5], ['i' => 2, 'f' => 1.5]],
                    [['i' => 3, 'f' => 2.0], ['i' => 4, 'f' => 3.0000005]]
                ]
            ],
            $s->getFields()
        );
    }

    public function testInvalidValue()
    {
        try {
            $errors = null;
            $s = new Sanitizer('
                {
                    "a": -1,
                    "i": 1.2,
                    "f": "1.0$",
                    "s": [1,2],
                    "a2": ["str", 1.2, {"a":1}, [1,2]],
                    "m": {"i1":"str", "f1":[1,2], "a1":10, "m1":1.5, "m2":{"a":11.5}},
                    "p1": "260557",
                    "p2": "+89240078897",
                    "p3": "+7 924 007-88-97",
                    "p4": "8(924)00-788-97"
                }',
                [
                    'a' => [Type::ARRAY, Type::STRING],
                    'i' => Type::INT,
                    'f' => Type::FLOAT,
                    's' => Type::STRING,
                    'a2' => [Type::ARRAY, Type::INT],
                    'm' => [Type::MAP, [
                        'i1' => Type::INT,
                        'f1' => Type::FLOAT,
                        'a1' => [Type::ARRAY, Type::STRING],
                        'm1' => [Type::MAP, []],
                        'm2' => [Type::MAP, ['a' => [Type::ARRAY, Type::INT]]]
                    ]],
                    'p1' => Type::PHONE,
                    'p2' => Type::PHONE,
                    'p3' => Type::PHONE,
                    'p4' => Type::PHONE,
                ]
            );
        } catch (SanitizerException $exception) {
            $errors = $exception->getErrors();
        }
        $this->assertContainsOnlyInstancesOf('Lindrid\Sanitizer\Result\Issue', $errors);
        $this->assertEquals(IssueType::ERROR, $errors[0]->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $errors[0]->getCode());
        $this->assertEquals('Значение -1 поля "a" невозможно преобразовать к типу Type::ARRAY!',
            $errors[0]->getMessage());
        $this->assertEquals('Значение 1.2 поля "i" невозможно преобразовать к типу Type::INT!',
            $errors[1]->getMessage());
        $this->assertEquals('Значение "1.0$" поля "f" невозможно преобразовать к типу Type::FLOAT!',
            $errors[2]->getMessage());
        $this->assertEquals('Значение [1,2] поля "s" невозможно преобразовать к типу Type::STRING!',
            $errors[3]->getMessage());

        $this->assertEquals(IssueType::ERROR, $errors[4]->getType());
        $this->assertEquals(ErrorCode::INVALID_ARRAY_ELEMENT_VALUE, $errors[4]->getCode());
        $this->assertEquals('Значение "str" массива "a2" невозможно преобразовать к Type::INT!',
            $errors[4]->getMessage());
        $this->assertEquals('Значение 1.2 массива "a2" невозможно преобразовать к Type::INT!',
            $errors[5]->getMessage());
        $this->assertEquals('Значение {"a":1} массива "a2" невозможно преобразовать к Type::INT!',
            $errors[6]->getMessage());
        $this->assertEquals('Значение [1,2] массива "a2" невозможно преобразовать к Type::INT!',
            $errors[7]->getMessage());
        $this->assertEquals('Значение "str" поля "m:i1" невозможно преобразовать к типу Type::INT!',
            $errors[8]->getMessage());
        $this->assertEquals('Значение [1,2] поля "m:f1" невозможно преобразовать к типу Type::FLOAT!',
            $errors[9]->getMessage());
        $this->assertEquals('Значение 10 поля "m:a1" невозможно преобразовать к типу Type::ARRAY!',
            $errors[10]->getMessage());
        $this->assertEquals('Значение 1.5 поля "m:m1" невозможно преобразовать к типу Type::MAP!',
            $errors[11]->getMessage());
        $this->assertEquals('Значение 11.5 поля "m:m2:a" невозможно преобразовать к типу Type::ARRAY!',
            $errors[12]->getMessage());

        $this->assertEquals('Значение "260557" поля "p1" невозможно преобразовать к типу Type::PHONE!',
            $errors[13]->getMessage());
        $this->assertEquals('Значение "+89240078897" поля "p2" невозможно преобразовать к типу Type::PHONE!',
            $errors[14]->getMessage());
        $this->assertEquals('Значение "+7 924 007-88-97" поля "p3" невозможно преобразовать к типу Type::PHONE!',
            $errors[15]->getMessage());
        $this->assertEquals('Значение "8(924)00-788-97" поля "p4" невозможно преобразовать к типу Type::PHONE!',
            $errors[16]->getMessage());
    }

    public function testErrorsAndWarningsAsOneMessage()
    {
        try {
            $message = $warning = null;
            $s = new Sanitizer(
                '{"a":0, "m":{"i":"1","f":"1.5","m1":{"p":1}}, "b":"abc"}',
                ['a' => Type::INT, 'm' => [Type::MAP, ['i' => Type::INT, 'm1' => [Type::MAP, []]]]]
            );
        } catch (SanitizerException $sanitizerException) {
            $message = $sanitizerException->getMessage();
            $warning = $sanitizerException->getWarning(0);
        }
        $this->assertEquals(IssueType::WARNING, $warning->getType());
        $this->assertEquals(WarningCode::NOT_EQUAL_COUNT, $warning->getCode());
        $this->assertEquals('Количество JSON полей (3 шт.) не совпадает с количеством типов полей (2 шт.) в корне',
            $warning->getMessage()
        );
        $this->assertEquals("Warnings: \nКоличество JSON полей (3 шт.) не совпадает с количеством типов полей (2 шт.) в корне\nКоличество JSON полей (3 шт.) не совпадает с количеством типов полей (2 шт.) для поля m\nКоличество JSON полей (1 шт.) не совпадает с количеством типов полей (0 шт.) для поля m:m1\nErrors: \nТип поля \"m:f\" не задан!\nТип поля \"m:m1:p\" не задан!\nТип поля \"b\" не задан!\n",
            $message);
    }

    public function testInvalidScalarFieldType()
    {
        try {
            $errors = null;
            $s = new Sanitizer(
                '{"v":1, "v2":2, "v3":3, "m":{"v":1}, "a":[[1,2]]}',
                [
                    'v' => 'int', 'v2' => 55,
                    'v3' => new ReflectionClass(__CLASS__),
                    'm' => [Type::MAP, ['v' => 55]],
                    'a' => [Type::ARRAY, [Type::ARRAY, 50]],
                ]
            );
        } catch (SanitizerException $exception) {
            $errors = $exception->getErrors();
        }
        $this->assertContainsOnlyInstancesOf('Lindrid\Sanitizer\Result\Issue', $errors);
        $this->assertEquals(IssueType::ERROR, $errors[0]->getType());
        $this->assertEquals(ErrorCode::INVALID_SCALAR_FIELD_TYPE, $errors[0]->getCode());
        $this->assertEquals('Поле "v" задано несуществующим скалярным типом "int". Используйте константы Type::',
            $errors[0]->getMessage()
        );
        $this->assertEquals('Поле "v2" задано несуществующим скалярным типом 55. Используйте константы Type::',
            $errors[1]->getMessage()
        );
        $this->assertEquals('Поле "v3" задано несуществующим скалярным типом {"name":"SanitizerTest"}. Используйте константы Type::',
            $errors[2]->getMessage()
        );
        $this->assertEquals('Поле "m:v" задано несуществующим скалярным типом 55. Используйте константы Type::',
            $errors[3]->getMessage()
        );
        $this->assertEquals('Поле "a:0:0" задано несуществующим скалярным типом 50. Используйте константы Type::',
            $errors[4]->getMessage()
        );
        $this->assertEquals('Поле "a:0:1" задано несуществующим скалярным типом 50. Используйте константы Type::',
            $errors[5]->getMessage()
        );
    }

    public function testInvalidMapFieldType()
    {
        try {
            $errors = null;
            $s = new Sanitizer(
                '{
                    "m1":{}, 
                    "m2":{}, 
                    "m3":{"a":"str"}, 
                    "m4":{}, 
                    "m5": {"a":"str"},
                    "m6": {"m_1":{"m_2":{"m_3":{"i":10}}}}
                }',
                [
                    'm1' => ['array', []],
                    'm2' => [70, []],
                    'm3' => [Type::ARRAY, ['a' => Type::STRING]],
                    'm4' => [new ReflectionClass(__CLASS__), ['a' => Type::STRING]],
                    'm5' => [Type::MAP, [Type::FLOAT, 2.4, "sdasd"]],
                    'm6' => [Type::MAP,
                        ['m_1' => [Type::MAP,
                            ['m_2' => [Type::MAP,
                                ['m_3' => [Type::PHONE,
                                    ['i' => Type::INT]
                                ]]
                            ]]
                        ]]
                    ]
                ]
            );
        } catch (SanitizerException $exception) {
            $errors = $exception->getErrors();
        }
        $this->assertContainsOnlyInstancesOf('Lindrid\Sanitizer\Result\Issue', $errors);
        $this->assertEquals(IssueType::ERROR, $errors[0]->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $errors[0]->getCode());
        $this->assertEquals('Составной тип поля "m1" имеет некорректное описание ["array",[]]!',
            $errors[0]->getMessage()
        );
        $this->assertEquals('Составной тип поля "m2" имеет некорректное описание [70,[]]!',
            $errors[1]->getMessage()
        );
        $this->assertEquals('Составной тип поля "m3" имеет некорректное описание [Type::ARRAY,{"a":Type::STRING}]!',
            $errors[2]->getMessage()
        );
        $this->assertEquals('Составной тип поля "m4" имеет некорректное описание [{"name":"SanitizerTest"},{"a":Type::STRING}]!',
            $errors[3]->getMessage()
        );
        $this->assertEquals('Составной тип поля "m5" имеет некорректное описание [Type::MAP,[Type::FLOAT,2.4,"sdasd"]]!',
            $errors[4]->getMessage()
        );
        $this->assertEquals('Составной тип поля "m6:m_1:m_2:m_3" имеет некорректное описание [Type::PHONE,{"i":Type::INT}]!',
            $errors[5]->getMessage()
        );
    }

    public function testInvalidArrayFieldType()
    {
        try {
            $errors = null;
            $s = new Sanitizer(
                '{"a1":[], "a2":[], "a3":[], "a4":[], "a5": [], "a6":[]}',
                [
                    'a1' => ['Type::Array', Type::INT],
                    'a2' => [70, Type::FLOAT],
                    'a3' => [Type::INT, Type::INT],
                    'a4' => [new ReflectionClass(__CLASS__), Type::STRING],
                    'a5' => [Type::ARRAY, 50],
                    'a6' => [Type::ARRAY, Type::ARRAY],
                ]
            );
        } catch (SanitizerException $exception) {
            $errors = $exception->getErrors();
        }
        $this->assertContainsOnlyInstancesOf('Lindrid\Sanitizer\Result\Issue', $errors);
        $this->assertEquals(IssueType::ERROR, $errors[0]->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $errors[0]->getCode());
        $this->assertEquals('Составной тип поля "a1" имеет некорректное описание ["Type::Array",Type::INT]!',
            $errors[0]->getMessage()
        );
        $this->assertEquals('Составной тип поля "a2" имеет некорректное описание [70,Type::FLOAT]!',
            $errors[1]->getMessage()
        );
        $this->assertEquals('Составной тип поля "a3" имеет некорректное описание [Type::INT,Type::INT]!',
            $errors[2]->getMessage()
        );
        $this->assertEquals('Составной тип поля "a4" имеет некорректное описание [{"name":"SanitizerTest"},Type::STRING]!',
            $errors[3]->getMessage()
        );
        $this->assertEquals('Составной тип поля "a5" имеет некорректное описание [Type::ARRAY,50]!',
            $errors[4]->getMessage()
        );
        $this->assertEquals('Составной тип поля "a6" имеет некорректное описание [Type::ARRAY,Type::ARRAY]!',
            $errors[5]->getMessage()
        );
    }
}
