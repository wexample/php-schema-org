## What it is

The schema.org vocabulary of one release — 30.1 — as schema.org itself defines it, kept as data: each type and the types it descends from, each property with the types it belongs to (its domains) and what it expects (its ranges), each enumeration's members. `SchemaOrgVocabulary` reads it to check a description and to write its values as schema.org reads them. Framework-agnostic; symfony-seo builds on it.

It is not a builder of JSON-LD: to write one by hand in PHP, `spatie/schema-org` does that. This package is for the descriptions produced from something else — attributes on an entity, configuration —, which need the vocabulary at runtime.

## Checking a description

```php
$vocabulary = new SchemaOrgVocabulary();

$vocabulary->getViolations([
    '@type' => 'Person',
    'nmae' => 'Jane',
    'knows' => ['@type' => 'Persn'],
]);
// ['"nmae" is not a schema.org property.', 'knows: "Persn" is not a schema.org type.']
```

Reported, one sentence each: a type it does not have, a property it does not have, one its type does not have (inheritance included — a LocalBusiness has an Organization's and a Place's), one schema.org replaced (`actors` by `actor`). Nested objects and lists are checked in turn; the JSON-LD keywords (`@id`, `@context`…) are left alone, and so is an object without `@type`.

## Writing a value

`write($property, $value)` gives the value as the property reads it, trying the data types of its range in `SchemaOrgDataType`'s order — the one losing nothing first:

| Value | Range | Written |
|---|---|---|
| `DateTimeInterface` | `Date` or `DateTime` (`dateCreated`) | `2022-03-14T10:30:00+01:00` |
| `DateTimeInterface` | `Date` (`birthDate`) | `2022-03-14` |
| `int` | `Date` | `"2022"` |
| `DateInterval` | `Duration` | `PT1H30M` |
| `'InStock'`, or a backed enum of that value | `ItemAvailability` | `https://schema.org/InStock` |
| `int` | `Text` | `"3"` |

Anything else — a string, an object, a list, a property outside the vocabulary — comes back as it is: an object is for its own normalizer to describe, never cast to text.

## Another release

```bash
bin/schema-org-vocabulary 30.1
```

downloads the release's JSON-LD from schema.org and writes `resources/vocabulary.php` again, one term a line, so that a new release differs from the last by what it changed. The releases are listed at https://schema.org/docs/releases.html.
