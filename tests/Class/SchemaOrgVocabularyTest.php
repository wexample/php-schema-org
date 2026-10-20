<?php

namespace Wexample\PhpSchemaOrg\Tests\Class;

use PHPUnit\Framework\TestCase;
use Wexample\PhpSchemaOrg\Class\SchemaOrgVocabulary;

class SchemaOrgVocabularyTest extends TestCase
{
    private SchemaOrgVocabulary $vocabulary;

    protected function setUp(): void
    {
        $this->vocabulary = new SchemaOrgVocabulary();
    }

    public function testAMomentKeepsItsTimeOnlyWhereTheRangeTakesOne(): void
    {
        $moment = new \DateTimeImmutable('2022-03-14 10:30:00+01:00');

        // Date or DateTime.
        $this->assertSame('2022-03-14T10:30:00+01:00', $this->vocabulary->write('dateCreated', $moment));
        // Date only.
        $this->assertSame('2022-03-14', $this->vocabulary->write('birthDate', $moment));
    }

    public function testAYearIsADate(): void
    {
        $this->assertSame('2022', $this->vocabulary->write('dateCreated', 2022));
    }

    public function testAValueIsWrittenAsItsPropertyExpectsIt(): void
    {
        $this->assertSame('PT1H30M', $this->vocabulary->write('duration', new \DateInterval('PT1H30M')));
        $this->assertSame('https://schema.org/InStock', $this->vocabulary->write('availability', 'InStock'));
        $this->assertSame(3, $this->vocabulary->write('numberOfPages', 3));
        $this->assertSame('3', $this->vocabulary->write('name', 3));
    }

    public function testWhatIsNoValueOfTheRangeIsLeftAsItIs(): void
    {
        $object = new \stdClass();
        $media = new class implements \Stringable {
            public function __toString(): string
            {
                return 'cover.png';
            }
        };

        $this->assertSame($object, $this->vocabulary->write('author', $object));
        // URL or ImageObject: an object is left to be described.
        $this->assertSame($media, $this->vocabulary->write('image', $media));
        $this->assertSame('Jane', $this->vocabulary->write('name', 'Jane'));
        $this->assertSame(2022, $this->vocabulary->write('notAProperty', 2022));
    }

    public function testAPropertyIsInheritedFromEveryParent(): void
    {
        // An Organization and a Place.
        $this->assertTrue($this->vocabulary->isPropertyOf('founder', 'LocalBusiness'));
        $this->assertTrue($this->vocabulary->isPropertyOf('geo', 'LocalBusiness'));
        $this->assertFalse($this->vocabulary->isPropertyOf('birthDate', 'LocalBusiness'));
    }

    public function testWhatSchemaOrgWouldNotReadIsReported(): void
    {
        $this->assertSame([
            '"nmae" is not a schema.org property.',
            '"actors" is replaced by "actor" in schema.org.',
            '"foundingDate" is not a property of Person.',
            'knows: "Persn" is not a schema.org type.',
        ], $this->vocabulary->getViolations([
            '@context' => SchemaOrgVocabulary::CONTEXT,
            '@type' => 'Person',
            'nmae' => 'Jane',
            'actors' => [],
            'foundingDate' => '2000',
            // Its properties are not checked against a type nobody knows.
            'knows' => ['@type' => 'Persn', 'birthDate' => '1990'],
        ]));
    }

    public function testAValidDescriptionHasNoViolation(): void
    {
        $this->assertSame([], $this->vocabulary->getViolations([
            '@type' => 'CreativeWork',
            'name' => 'One',
            'dateCreated' => '2022',
            'author' => ['@id' => '/#person'],
            'image' => [['@type' => 'ImageObject', 'contentUrl' => 'https://example.com/a.png']],
        ]));
    }
}
