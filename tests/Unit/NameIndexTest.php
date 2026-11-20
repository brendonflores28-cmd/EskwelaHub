<?php

use App\Support\NameIndex;

it('normalizes accents, case and punctuation', function () {
    expect(NameIndex::normalize('  DE LA PEÑA,   José-Luis '))->toBe('de la pena jose luis');
});

it('ignores word order when scoring', function () {
    $name = NameIndex::tokens('Juan Dela Cruz');

    expect(NameIndex::score(['cruz', 'juan'], $name))->toBeGreaterThan(0.9);
});

it('scores a small typo high and an unrelated name low', function () {
    $name = ['marasigan', 'arnel'];

    expect(NameIndex::score(['marasagin'], $name))->toBeGreaterThan(0.6)
        ->and(NameIndex::score(['villanueva'], $name))->toBeLessThan(0.5);
});

it('builds phonetic codes per word', function () {
    expect(NameIndex::metaphone('santos juan'))->toBe(metaphone('santos').' '.metaphone('juan'));
});
