<?php

namespace Wexample\PhpSchemaOrg\Enum;

/**
 * The values schema.org writes as text rather than as an object: what a
 * property's range names when it expects a date, a number, a duration. Every
 * other data type of the vocabulary descends from one of these — a URL is a
 * Text, an Integer a Number, a Duration a Quantity.
 *
 * Declared in the order a value is tried in, the type losing nothing of it
 * first: a moment is a DateTime before it is a Date, a number a Number before
 * it is text.
 */
enum SchemaOrgDataType: string
{
    case DATE_TIME = 'DateTime';
    case DATE = 'Date';
    case TIME = 'Time';
    case NUMBER = 'Number';
    case BOOLEAN = 'Boolean';
    case QUANTITY = 'Quantity';
    case TEXT = 'Text';

    /**
     * The PHP value as schema.org reads this type — ISO 8601 for a moment or
     * a duration, a year alone being a Date —, or null when it cannot be one.
     */
    public function write(mixed $value): string|int|float|bool|null
    {
        return match ($this) {
            self::DATE_TIME => $value instanceof \DateTimeInterface ? $value->format(\DateTimeInterface::ATOM) : null,
            self::DATE => match (true) {
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                is_int($value) => (string) $value,
                default => null,
            },
            self::TIME => $value instanceof \DateTimeInterface ? $value->format('H:i:sP') : null,
            self::NUMBER => is_int($value) || is_float($value) ? $value : null,
            self::BOOLEAN => is_bool($value) ? $value : null,
            self::QUANTITY => $value instanceof \DateInterval ? self::writeDuration($value) : null,
            self::TEXT => is_int($value) || is_float($value) || $value instanceof \Stringable ? (string) $value : null,
        };
    }

    /**
     * `P1Y2M3DT4H5M6S`, its empty parts left out; an empty interval `PT0S`.
     */
    private static function writeDuration(\DateInterval $interval): string
    {
        $date = implode('', array_filter([
            $interval->y ? $interval->y.'Y' : null,
            $interval->m ? $interval->m.'M' : null,
            $interval->d ? $interval->d.'D' : null,
        ]));
        $time = implode('', array_filter([
            $interval->h ? $interval->h.'H' : null,
            $interval->i ? $interval->i.'M' : null,
            $interval->s ? $interval->s.'S' : null,
        ]));

        if ('' === $date && '' === $time) {
            return 'PT0S';
        }

        return ($interval->invert ? '-' : '').'P'.$date.('' !== $time ? 'T'.$time : '');
    }
}
