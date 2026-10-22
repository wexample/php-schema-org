<?php

namespace Wexample\PhpSchemaOrg\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Wexample\PhpSchemaOrg\Helper\SchemaOrgVocabularyHelper;

class SchemaOrgVocabularyHelperTest extends TestCase
{
    public function testAReleaseIsReducedToItsTypesPropertiesAndMembers(): void
    {
        $this->assertSame([
            'version' => '30.1',
            'types' => [
                'Enumeration' => ['Thing'],
                'ItemAvailability' => ['Enumeration'],
                'Text' => [],
                'Thing' => [],
            ],
            'properties' => [
                'availability' => ['domains' => ['Thing'], 'ranges' => ['ItemAvailability']],
                'oldName' => ['domains' => ['Thing'], 'ranges' => ['Text'], 'supersededBy' => 'name'],
            ],
            'members' => [
                'InStock' => ['ItemAvailability'],
            ],
        ], SchemaOrgVocabularyHelper::fromJsonLd(['@graph' => [
            ['@id' => 'schema:Thing', '@type' => 'rdfs:Class', 'rdfs:comment' => 'Anything.'],
            ['@id' => 'schema:Text', '@type' => ['rdfs:Class', 'schema:DataType']],
            ['@id' => 'schema:Enumeration', '@type' => 'rdfs:Class', 'rdfs:subClassOf' => ['@id' => 'schema:Thing']],
            // Mapped onto another vocabulary: left out.
            [
                '@id' => 'schema:ItemAvailability',
                '@type' => 'rdfs:Class',
                'rdfs:subClassOf' => [['@id' => 'schema:Enumeration'], ['@id' => 'gs1:Availability']],
            ],
            ['@id' => 'schema:InStock', '@type' => 'schema:ItemAvailability'],
            [
                '@id' => 'schema:availability',
                '@type' => 'rdf:Property',
                'schema:domainIncludes' => ['@id' => 'schema:Thing'],
                'schema:rangeIncludes' => ['@id' => 'schema:ItemAvailability'],
            ],
            [
                '@id' => 'schema:oldName',
                '@type' => 'rdf:Property',
                'schema:domainIncludes' => ['@id' => 'schema:Thing'],
                'schema:rangeIncludes' => ['@id' => 'schema:Text'],
                'schema:supersededBy' => ['@id' => 'schema:name'],
            ],
            ['@id' => 'gs1:Availability', '@type' => 'rdfs:Class'],
        ]], '30.1'));
    }
}
