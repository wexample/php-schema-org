<?php

namespace Wexample\PhpSchemaOrg\Helper;

/**
 * schema.org's own definition of its vocabulary — the JSON-LD file of a
 * release — reduced to what a description is checked and written against:
 * each type's parents, each property's domains and ranges, each enumeration's
 * members. What the vocabulary maps from other ones (`owl:`, `skos:`, `fibo-`…)
 * is left out, and so are the comments.
 */
final class SchemaOrgVocabularyHelper
{
    private const string PREFIX = 'schema:';

    private const string CLASS_TYPE = 'rdfs:Class';
    private const string PROPERTY_TYPE = 'rdf:Property';
    private const string DATA_TYPE = 'schema:DataType';

    /**
     * @param array{'@graph': list<array<string, mixed>>} $document
     *
     * @return array{
     *     version: string,
     *     types: array<string, list<string>>,
     *     properties: array<string, array{domains: list<string>, ranges: list<string>, supersededBy?: string}>,
     *     members: array<string, list<string>>
     * } every name sorted, for a release to differ from the last by what
     *   it changed
     */
    public static function fromJsonLd(array $document, string $version): array
    {
        $types = [];
        $properties = [];
        $members = [];

        foreach ($document['@graph'] as $term) {
            if (null === $name = self::nameOf($term['@id'] ?? null)) {
                continue;
            }

            $kinds = (array) $term['@type'];

            if (in_array(self::CLASS_TYPE, $kinds, true)) {
                $types[$name] = self::namesOf($term['rdfs:subClassOf'] ?? []);
            } elseif (in_array(self::PROPERTY_TYPE, $kinds, true)) {
                $properties[$name] = [
                    'domains' => self::namesOf($term['schema:domainIncludes'] ?? []),
                    'ranges' => self::namesOf($term['schema:rangeIncludes'] ?? []),
                ] + array_filter([
                    'supersededBy' => self::namesOf($term['schema:supersededBy'] ?? [])[0] ?? null,
                ]);
            }

            // An enumeration's member is typed by its enumeration: InStock an
            // ItemAvailability.
            $enumerations = self::namesOf(array_map(
                static fn (string $kind): array => ['@id' => $kind],
                array_diff($kinds, [self::CLASS_TYPE, self::PROPERTY_TYPE, self::DATA_TYPE])
            ));

            if ([] !== $enumerations) {
                $members[$name] = $enumerations;
            }
        }

        ksort($types);
        ksort($properties);
        ksort($members);

        return [
            'version' => $version,
            'types' => $types,
            'properties' => $properties,
            'members' => $members,
        ];
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>> $references one
     *                                                                     `{"@id": …}` or a list of them
     *
     * @return list<string>
     */
    private static function namesOf(array $references): array
    {
        $names = [];

        foreach (isset($references['@id']) ? [$references] : $references as $reference) {
            if (null !== $name = self::nameOf($reference['@id'] ?? null)) {
                $names[] = $name;
            }
        }

        sort($names);

        return array_values(array_unique($names));
    }

    private static function nameOf(mixed $id): ?string
    {
        return is_string($id) && str_starts_with($id, self::PREFIX)
            ? substr($id, strlen(self::PREFIX))
            : null;
    }
}
