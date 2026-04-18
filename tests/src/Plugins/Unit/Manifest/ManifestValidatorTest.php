<?php

declare(strict_types=1);

use Capell\Plugins\Manifest\Exceptions\ManifestValidationException;
use Capell\Plugins\Manifest\ManifestValidator;

beforeEach(function (): void {
    $this->validator = new ManifestValidator;
});

test('accepts the full plugin fixture', function (): void {
    $json = $this->loadFixture('manifests/valid-full-plugin.json');
    $result = $this->validator->validate($json);
    expect($result->isValid)->toBeTrue();
    expect($result->errors)->toBeEmpty();
});

test('accepts the free widget fixture', function (): void {
    $json = $this->loadFixture('manifests/valid-free-widget.json');
    $result = $this->validator->validate($json);
    expect($result->isValid)->toBeTrue();
});

test('rejects fixture with missing name', function (): void {
    $json = $this->loadFixture('manifests/invalid-missing-name.json');
    $result = $this->validator->validate($json);
    expect($result->isValid)->toBeFalse();
    expect($result->errors[0] ?? '')->toContain('name');
});

test('rejects fixture with bad semver', function (): void {
    $json = $this->loadFixture('manifests/invalid-bad-semver.json');
    $result = $this->validator->validate($json);
    expect($result->isValid)->toBeFalse();
    expect(implode(' ', $result->errors))->toContain('version');
});

test('rejects fixture with unknown capability', function (): void {
    $json = $this->loadFixture('manifests/invalid-unknown-capability.json');
    $result = $this->validator->validate($json);
    expect($result->isValid)->toBeFalse();
    expect(implode(' ', $result->errors))->toContain('writes_brains');
});

test('validateOrFail throws on invalid input', function (): void {
    $json = $this->loadFixture('manifests/invalid-missing-name.json');
    $this->validator->validateOrFail($json);
})->throws(ManifestValidationException::class);

test('hydrate returns a PluginManifestData from a valid manifest', function (): void {
    $json = $this->loadFixture('manifests/valid-full-plugin.json');
    $manifest = $this->validator->hydrate($json);
    expect($manifest->name)->toBe('acme/super-widget');
});
