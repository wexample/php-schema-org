<?php

namespace Wexample\PhpSchemaOrg\Class;

use Wexample\PhpSchemaOrg\Enum\SchemaOrgDataType;

/**
 * The schema.org vocabulary of one release, as schema.org itself defines it:
 * which types there are and what they descend from, which property a type
 * has, what each one expects. A description is checked against it — a type
 * misspelt, a property its type does not have, one schema.org replaced — and
 * its values written as their property reads them: a year in a `dateCreated`,
 * a moment in a `startDate`, a duration in a `duration`.
 *
 * Bundled in `resources/vocabulary.php`, which `bin/schema-org-vocabulary`
 * builds from the release's JSON-LD.
 */
final class SchemaOrgVocabulary
{
    public const string CONTEXT = 'https://schema.org';

    /**
     * @var array{
     *     version: string,
     *     types: array<string, list<string>>,
     *     properties: array<string, array{domains: list<string>, ranges: list<string>, supersededBy?: string}>,
     *     members: array<string, list<string>>
     * }
     */
    private readonly array $vocabulary;

    /**
     * @param array|null $vocabulary another than the bundled one, built by
     *                               SchemaOrgVocabularyHelper::fromJsonLd()
     */
    public function __construct(?array $vocabulary = null)
    {
        $this->vocabulary = $vocabulary ?? require dirname(__DIR__, 2).'/resources/vocabulary.php';
    }

    /** The schema.org release the vocabulary is, `30.1`. */
    public function getVersion(): string
    {
        return $this->vocabulary['version'];
    }

    public function isType(string $type): bool
    {
        return isset($this->vocabulary['types'][$type]);
    }

    public function isProperty(string $property): bool
    {
        return isset($this->vocabulary['properties'][$property]);
    }

    /**
     * @return list<string> the type, then every type it descends from — two
     *                      parents for some: a LocalBusiness is an
     *                      Organization and a Place
     */
    public function getAncestors(string $type): array
    {
        $ancestors = [];
        $pending = [$type];

        while (null !== $current = array_shift($pending)) {
            if (! in_array($current, $ancestors, true)) {
                $ancestors[] = $current;
                array_push($pending, ...$this->vocabulary['types'][$current] ?? []);
            }
        }

        return $ancestors;
    }

    /** Whether the type, or one it descends from, has the property. */
    public function isPropertyOf(string $property, string $type): bool
    {
        return [] !== array_intersect(
            $this->vocabulary['properties'][$property]['domains'] ?? [],
            $this->getAncestors($type)
        );
    }

    /**
     * @return list<string> what the property expects: types, data types
     */
    public function getRanges(string $property): array
    {
        return $this->vocabulary['properties'][$property]['ranges'] ?? [];
    }

    /** The property schema.org replaced this one with, if it did. */
    public function getSupersededBy(string $property): ?string
    {
        return $this->vocabulary['properties'][$property]['supersededBy'] ?? null;
    }

    /** What a data type is written as: Integer a Number, URL a Text. */
    public function getDataType(string $type): ?SchemaOrgDataType
    {
        foreach ($this->getAncestors($type) as $ancestor) {
            if ($this->isType($ancestor) && $dataType = SchemaOrgDataType::tryFrom($ancestor)) {
                return $dataType;
            }
        }

        return null;
    }

    /**
     * @return list<string> the enumerations the member belongs to: InStock to
     *                      ItemAvailability
     */
    public function getEnumerations(string $member): array
    {
        return $this->vocabulary['members'][$member] ?? [];
    }

    /**
     * A value as the property reads it: in the first data type of its range
     * the value can be, in SchemaOrgDataType's order — so that a moment keeps
     * its time where a DateTime is accepted, and loses it where only a Date
     * is —; a member of an enumeration it expects, by name or as a backed
     * enum's value, as the member's address. Any other value — an object, a
     * list, a property outside the vocabulary — is handed back as it is.
     */
    public function write(string $property, mixed $value): mixed
    {
        $ranges = $this->getRanges($property);
        $dataTypes = array_filter(array_map($this->getDataType(...), $ranges));

        foreach (SchemaOrgDataType::cases() as $dataType) {
            if (in_array($dataType, $dataTypes, true) && null !== $written = $dataType->write($value)) {
                return $written;
            }
        }

        $member = $value instanceof \BackedEnum ? $value->value : $value;

        if (is_string($member) && $this->isMemberOf($member, $ranges)) {
            return self::CONTEXT.'/'.$member;
        }

        return $value;
    }

    /**
     * What schema.org would not read in a description, one sentence each: a
     * type it does not have, a property its type does not have — checked
     * against the types it knows, none known being said once — or that it
     * replaced. Its nested objects are checked in turn; the JSON-LD keywords
     * (`@id`, `@context`…) are left alone, and so is an object without
     * `@type`, a reference to a node described elsewhere.
     *
     * @param array<string, mixed> $object
     *
     * @return list<string>
     */
    public function getViolations(array $object, string $path = ''): array
    {
        if (array_is_list($object)) {
            $violations = [];

            foreach ($object as $index => $item) {
                if (is_array($item)) {
                    array_push($violations, ...$this->getViolations($item, $path.'['.$index.']'));
                }
            }

            return $violations;
        }

        if (! isset($object['@type'])) {
            return [];
        }

        $violations = [];
        $types = [];

        foreach ((array) $object['@type'] as $type) {
            if ($this->isType($type)) {
                $types[] = $type;
            } else {
                $violations[] = sprintf('%s"%s" is not a schema.org type.', self::prefix($path), $type);
            }
        }

        foreach ($object as $name => $value) {
            if (str_starts_with($name, '@')) {
                continue;
            }

            if (! $this->isProperty($name)) {
                $violations[] = sprintf('%s"%s" is not a schema.org property.', self::prefix($path), $name);
            } elseif ($supersededBy = $this->getSupersededBy($name)) {
                $violations[] = sprintf('%s"%s" is replaced by "%s" in schema.org.', self::prefix($path), $name, $supersededBy);
            } elseif ([] !== $types && ! array_filter($types, fn (string $type): bool => $this->isPropertyOf($name, $type))) {
                $violations[] = sprintf('%s"%s" is not a property of %s.', self::prefix($path), $name, implode(', ', $types));
            }

            if (is_array($value)) {
                array_push($violations, ...$this->getViolations($value, ('' === $path ? '' : $path.'.').$name));
            }
        }

        return $violations;
    }

    /**
     * @param list<string> $ranges
     */
    private function isMemberOf(string $member, array $ranges): bool
    {
        foreach ($this->getEnumerations($member) as $enumeration) {
            if ([] !== array_intersect($this->getAncestors($enumeration), $ranges)) {
                return true;
            }
        }

        return false;
    }

    private static function prefix(string $path): string
    {
        return '' === $path ? '' : $path.': ';
    }
}
