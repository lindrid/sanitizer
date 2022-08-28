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

    public function testGetIntField()
    {
        $s = new Sanitizer('{"id": 10}', ['id' => Type::INT]);
        $fields = $s->getFields();
        $this->assertEquals(['id' => 10], $fields);
    }

    public function testGetFloatField()
    {
        $s = new Sanitizer('{"val": 1.5}', ['val' => Type::FLOAT]);
        $fields = $s->getFields();
        $this->assertEquals(['val' => 1.5], $fields);
    }

    public function testGetStringField()
    {
        $s = new Sanitizer('{"str": "bed"}', ['str' => Type::STRING]);
        $fields = $s->getFields();
        $this->assertEquals(['str' => 'bed'], $fields);
    }

    public function testGetMapField()
    {
        $s = new Sanitizer('{"map": {"a": 0, "b": 1.5, "c": "str"}}',
            ['map' => [Type::MAP, ['a' => Type::INT, 'b' => Type::FLOAT, 'c' => Type::STRING]]]);
        $fields = $s->getFields();
        $this->assertEquals(['map' => ['a' => 0, 'b' => 1.5, 'c' => 'str']], $fields);
    }

    public function testEmptyArray()
    {
        $s = new Sanitizer('{"array": []}', ['array' => [Type::ARRAY]]);
        $fields = $s->getFields();
        $this->assertEquals(['array' => []], $fields);
    }

    public function testInvalidInt()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"i": []}', ['i' => Type::INT]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
    }

    public function testInvalidFloat()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"f": 1}', ['f' => Type::FLOAT]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
    }

    public function testInvalidMap()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"m": [1,"a",[3]]}', ['m' => Type::MAP]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
    }

    public function testInvalidArray()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"arr": {"a":1, "b":2}}', ['m' => [Type::ARRAY, Type::INT]]);
        } catch (SanitizerException $exception) {
            $issue = $exception->getIssue(0);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::INVALID_FIELD_VALUE, $issue->getCode());
    }

    /*public function testGetArrayField()
   {
       $s = new Sanitizer('{"array": [{"a":1},2,3,4]}', ['array' => [Type::ARRAY, Type::INT]]);
       $fields = $s->getData();
       $this->assertEquals(['array' => ['a','b','c','str']], $fields);
   }*/

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
    }

    public function testFieldTypeNotSet()
    {
        try {
            $issue = null;
            $s = new Sanitizer('{"a":0}', []);
        } catch (SanitizerException $sanitizerException) {
            $issue = $sanitizerException->getIssue(1);
        }
        $this->assertInstanceOf('Lindrid\Sanitizer\Result\Issue', $issue);
        $this->assertEquals(IssueType::ERROR, $issue->getType());
        $this->assertEquals(ErrorCode::FIELD_TYPE_NOT_SET, $issue->getCode());
    }

    /*public function testIntValidation()
    {
        $s = new Sanitizer('{"id": 10}', ['val' => Type::INT]);

    }*/
}
