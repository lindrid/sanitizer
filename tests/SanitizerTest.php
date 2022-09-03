<?php


use Lindrid\Sanitizer\Exceptions\SanitizerException;
use Lindrid\Sanitizer\Result\Codes\Warning as WarningCode;
use Lindrid\Sanitizer\Result\Codes\Error as ErrorCode;
use Lindrid\Sanitizer\Result\IssueType;
use Lindrid\Sanitizer\Sanitizer;
use Lindrid\Sanitizer\Type;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    public function testInstantiationOfSanitizer()
    {
        $s = new Sanitizer('{}', []);
        $this->assertInstanceOf('Lindrid\Sanitizer\Sanitizer', $s);
    }

    public function testGetFields()
    {
        $s = new Sanitizer('
            {
                "id": "10", 
                "val": "1.5e1", 
                "str": 1,
                "map": {"a": 1.0e1, "b": 2, "c": 1.5, "0":4},
                "array": ["10e-1", "10.0", "2.0e1"]
            }',
            [
                'id' => Type::INT,
                'val' => Type::FLOAT,
                'str' => Type::STRING,
                'map' => [Type::MAP, [0 => Type::INT, 'a' => Type::INT, 'b' => Type::FLOAT, 'c' => Type::STRING]],
                'array' => [Type::ARRAY, Type::INT],
            ]
        );
        $this->assertSame(
            [
                'id' => 10,
                'val' => 15.0,
                'str' => '1',
                'map' => ['a' => 10, 'b' => 2.0, 'c' => "1.5", 4],
                'array' => [1, 10, 20],
            ],
            $s->getFields()
        );
    }

    public function testInvalidIntValue()
    {
        try {
            $issues = null;
            $s = new Sanitizer('
                {
                    "a": -1.2,
                    "a2": ["str", 1.2, {"a":1}, [1,2]],
                    "m": {"i1":"str", "f1":[1,2], "a1":10, "m1":1.5}
                }',
                [
                    'a' => [Type::ARRAY, Type::STRING],
                    'a2' => [Type::ARRAY, Type::INT],
                    'm' => [Type::MAP, [
                        'i1' => Type::INT,
                        'f1' => Type::FLOAT,
                        'a1' => [Type::ARRAY, Type::STRING],
                        'm1' => [Type::MAP, []]
                    ]]
                ]
            );
        } catch (SanitizerException $exception) {
            $issues = $exception->getIssues();
            var_dump($issues);
        }
       /* $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issues);
        $this->assertEquals(IssueType::ERROR, $issues->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issues->getCode());
        $this->assertEquals('Значение поля i задано неверно: 1.2. Оно должно иметь тип Type::INT!',
            $issues->getMessage());*/
    }

    public function testInvalidFloatValue()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"f": "1.0$"}', ['f' => Type::FLOAT]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
    }

    public function testInvalidStringValue()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"s": [1,2]}', ['s' => Type::STRING]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
    }

    public function testInvalidMapValue()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"m": [1]}', ['m' => [Type::MAP, ['a' => Type::INT]]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
    }

    public function testInvalidArrayValue()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"arr": {"a":1, "b":2}}', ['arr' => [Type::ARRAY, Type::INT]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
        $this->assertEquals('Значение поля arr задано неверно: {"a":1,"b":2}. Оно должно иметь тип Type::ARRAY!',
            $issue->getMessage()
        );
    }

    public function testNotEqualCount()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"a":0}', ['a' => Type::INT, 'b' => Type::FLOAT]);
        } catch (SanitizerException $sanitizerException) {
            $issue = $sanitizerException->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::WARNING, $issue->getType());
        $this->assertEquals(WarningCode::NOT_EQUAL_COUNT, $issue->getCode());
        $this->assertEquals('Количество JSON полей (1 шт.) не совпадает с количеством типов полей (2 шт.) в корне',
            $issue->getMessage()
        );
    }

    public function testFieldTypeNotSet()
    {
        try {
            $warning = $error = null;
            $s = new Sanitizer('{"a":0}', []);
        } catch (SanitizerException $sanitizerException) {
            $warning = $sanitizerException->getIssue(0);
            $error = $sanitizerException->getIssue(1);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $warning);
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $error);
        $this->assertEquals(
            'Количество JSON полей (1 шт.) не совпадает с количеством типов полей (0 шт.) в корне',
            $warning->getMessage()
        );
        $this->assertEquals(
            'Тип поля a не задан!',
            $error->getMessage()
        );
    }

    public function testInvalidScalarFieldType()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": 1}', ['v' => 'int']);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_SCALAR_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Поле v задано несуществующим скалярным типом "int". Используйте константы Type::',
            $issue->getMessage()
        );
    }

    public function testInvalidScalarFieldType2()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": 1}', ['v' => 55]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_SCALAR_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Поле v задано несуществующим скалярным типом 55. Используйте константы Type::',
            $issue->getMessage()
        );
    }

    public function testInvalidScalarFieldType3()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": 1}', ['v' => new ReflectionClass(__CLASS__)]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_SCALAR_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Поле v задано несуществующим скалярным типом ["name"=>"SanitizerTest"]. '
            . 'Используйте константы Type::',
            $issue->getMessage()
        );
    }

    public function testInvalidMapFieldType()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": {}}', ['v' => ['array', []]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Поле v задано несуществующим составным типом ["array",[]]. Бывают 2 составных типа: '
            . 'Type::MAP, Type::ARRAY. Примеры корректной записи: [Type::MAP, ["first" => Type::INT, "second" => '
            . 'Type::FLOAT]], [Type::ARRAY, Type::INT]. Вместо скалярного Type::X, можно использовать вложенный '
            . '[Type::MAP, [...]] или [Type::ARRAY, Type::Y]',
            $issue->getMessage()
        );
    }

    public function testInvalidMapFieldType2()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": {}}', ['v' => [70, []]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Поле v задано несуществующим составным типом [70,[]]. Бывают 2 составных типа: '
            . 'Type::MAP, Type::ARRAY. Примеры корректной записи: [Type::MAP, ["first" => Type::INT, "second" => '
            . 'Type::FLOAT]], [Type::ARRAY, Type::INT]. Вместо скалярного Type::X, можно использовать вложенный '
            . '[Type::MAP, [...]] или [Type::ARRAY, Type::Y]',
            $issue->getMessage()
        );
    }

    public function testInvalidMapFieldType3()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": {"a":"str"}}', ['v' => [Type::INT, ['a' => Type::STRING]]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Поле v задано несуществующим составным типом [Type::INT,["a"=>Type::STRING]]. Бывают 2 составных типа: '
            . 'Type::MAP, Type::ARRAY. Примеры корректной записи: [Type::MAP, ["first" => Type::INT, "second" => '
            . 'Type::FLOAT]], [Type::ARRAY, Type::INT]. Вместо скалярного Type::X, можно использовать вложенный '
            . '[Type::MAP, [...]] или [Type::ARRAY, Type::Y]',
            $issue->getMessage()
        );
    }

    public function testInvalidMapFieldType4()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": {"a":"str"}}', ['v' => [
                new ReflectionClass(__CLASS__),
                ['a' => Type::STRING]
            ]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Поле v задано несуществующим составным типом [["name"=>"SanitizerTest"],["a"=>Type::STRING]]. Бывают 2 составных типа: '
            . 'Type::MAP, Type::ARRAY. Примеры корректной записи: [Type::MAP, ["first" => Type::INT, "second" => '
            . 'Type::FLOAT]], [Type::ARRAY, Type::INT]. Вместо скалярного Type::X, можно использовать вложенный '
            . '[Type::MAP, [...]] или [Type::ARRAY, Type::Y]',
            $issue->getMessage()
        );
    }

    public function testInvalidMapFieldType5()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": {"a":"str"}}', ['v' => [Type::MAP, [Type::FLOAT, 1, 2.4, "sdasd"]]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_MAP_FIELD_TYPE, $issue->getCode());
        $this->assertEquals('Тип MAP поля v имеет некорректное описание [Type::MAP,[Type::FLOAT,1,2.4,"sdasd"]]! ' .
            'Пример корректной записи: [Type::MAP, ["first" => Type::INT, "second" => Type::FLOAT]]. Вместо скалярного '
            . 'Type::X, можно использовать вложенный [Type::MAP, [...]] или [Type::ARRAY, Type::Y]',
            $issue->getMessage()
        );
    }

    public function testMapFieldTypeNotEqualCount()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": {"a":"str"}}', ['v' => [Type::MAP, ['a' => Type::STRING, 3]]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::WARNING, $issue->getType());
        $this->assertEquals(WarningCode::NOT_EQUAL_COUNT, $issue->getCode());
        $this->assertEquals('Количество JSON полей (1 шт.) не совпадает с количеством типов полей (2 шт.) для поля v',
            $issue->getMessage()
        );
    }

    public function testInvalidArrayFieldType()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": []}', ['v' => ['array', Type::INT]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $issue->getCode());
    }

    public function testInvalidArrayFieldType2()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": []}', ['v' => [70, Type::INT]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_NESTED_FIELD_TYPE, $issue->getCode());
    }

    public function testInvalidArrayFieldType3()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": []}', ['v' => [Type::ARRAY, 'int']]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals('Тип ARRAY поля v имеет некорректное описание [Type::ARRAY,"int"]! '
            . 'Пример корректной записи: '
            . '[Type::ARRAY, Type::INT]. Вместо скалярного Type::INT, можно использовать вложенный '
            . '[Type::MAP, ["first" => Type::INT, "second" => Type::FLOAT]] или [Type::ARRAY, Type::Y]',
            $issue->getMessage()
        );
    }

    public function testInvalidArrayFieldType4()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": []}', ['v' => [Type::ARRAY, 77]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_ARRAY_FIELD_TYPE, $issue->getCode());
    }

    public function testInvalidScalarFieldTypeInDepth()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"m": {"v":1}}', ['m' => [Type::MAP, ['v' => 55]]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_SCALAR_FIELD_TYPE, $issue->getCode());
    }

    public function testNestedInvalidFieldValue()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"v": {"a":{"b":{"c":1}}}}', ['v' =>
                [Type::MAP, ['a' => [Type::MAP, ['b' => [Type::ARRAY, Type::INT]]]]]
            ]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
        $this->assertEquals('Значение поля v:a:b задано неверно: {"c":1}. Оно должно иметь тип Type::ARRAY!',
            $issue->getMessage()
        );
    }

    public function testComplexJsonWithIssues()
    {
        try {
            $issues = null;
            $s = new Sanitizer('
                {
                    "a": {
                        "a1":{
                            "a2":[1.5, "string"],
                            "b2":"sss",
                            "c2":{}
                        }, 
                        "b1":1
                    }, 
                    "b":[1.1,1.2],
                    "c":[1,2,3]
                }',
                [
                    'a' => [Type::MAP, [
                        'a1' => [Type::MAP, [
                            'a2' => [Type::ARRAY, Type::INT],
                            'b2' => [Type::ARRAY, Type::STRING],
                            'c2' => Type::STRING
                        ]]
                    ]],
                    'b' => [Type::ARRAY, Type::FLOAT],
                    'c' => [Type::ARRAY, [Type::INT]]
                ]
            );
        } catch (SanitizerException $exception) {
            $issues = $exception->getIssues();
            var_dump($issues);
        }
        $this->assertContainsOnlyInstancesOf('Lindrid\Sanitizer\Result\Issue', $issues);
        //$this->assertEquals(IssueType::ERROR, $issue->getType());
        //$this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
        //$this->assertEquals('Значение поля v:a:b задано неверно: {"c":1}. '
         //   . 'Оно должно иметь тип Type::ARRAY и соответствовать описанию!',
         //   $issue->getMessage()
        //);/
    }

/*    public function FieldTypeNotSet()
    {
        try {
            $warning = $error = null;
            $s = new Sanitizer('{"a":0}', []);
        } catch (SanitizerException $sanitizerException) {
            $warning = $sanitizerException->getIssue(0);
            $error = $sanitizerException->getIssue(1);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $warning);
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $error);
        $this->assertEquals(
            'Количество JSON полей (1 шт.) не совпадает с количеством типов полей (0 шт.) в корне',
            $warning->getMessage()
        );
        $this->assertEquals(
            'Тип поля a не задан!',
            $error->getMessage()
        );
    }

    /*
     * { m:{a:[1,2],b:[3,4]} }  m => [Type::MAP, ['a' => [Type::ARRAY, Type::INT], 'b' => [Type::ARRAY, Type::INT]]
     * m:{a:{a1:1,a2:2},b:{b1:[1,2]}}  m => [Type::MAP, [
     *      'a' => [Type::MAP, ['a1' => Type::INT, 'b2' => Type::INT]
     *
     * m:a:a1
     * m:a:a2
     * m:b:b1
     * m:b:b2
     */

    /*
     * a:[1,2]          a => [Type::ARRAY, Type::INT]
     * a: [1.5, 'str']  a => [Type::ARRAY, Type::INT]
     * a:1              a => [Type::ARRAY, Type::INT]
     *
     * a:[[1,2],[3,4]]  a => [Type::ARRAY, [Type::ARRAY, Type::INT]]
     * a:[[1.5,2.5],[3.5,4.5]]  a => [Type::ARRAY, [Type::ARRAY, Type::INT]]
     */

    /*public function IntValidation()
    {
        $s = new Sanitizer('{"id": 10}', ['val' => Type::INT]);

    }*/
}
