<?php

use Xchimx\LaravelSecurity\Support\Severity;

it('normalizes severity strings case-insensitively', function (string $input, Severity $expected) {
    expect(Severity::fromString($input))->toBe($expected);
})->with([
    ['low', Severity::Low],
    ['LOW', Severity::Low],
    ['info', Severity::Low],
    ['none', Severity::Low],
    ['medium', Severity::Medium],
    ['moderate', Severity::Medium],
    ['MODERATE', Severity::Medium],
    [' high ', Severity::High],
    ['critical', Severity::Critical],
    ['something-else', Severity::Unknown],
    ['', Severity::Unknown],
]);

it('treats null as unknown severity', function () {
    expect(Severity::fromString(null))->toBe(Severity::Unknown);
});

it('compares severities against a threshold', function () {
    expect(Severity::Critical->isAtLeast(Severity::High))->toBeTrue()
        ->and(Severity::High->isAtLeast(Severity::High))->toBeTrue()
        ->and(Severity::Medium->isAtLeast(Severity::High))->toBeFalse()
        ->and(Severity::Low->isAtLeast(Severity::Medium))->toBeFalse()
        ->and(Severity::Unknown->isAtLeast(Severity::Low))->toBeFalse()
        ->and(Severity::Low->isAtLeast(Severity::Unknown))->toBeTrue();
});
