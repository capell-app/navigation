<?php

declare(strict_types=1);

use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Navigation\Health\NavigationHealthCheck;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

it('reports a compatible capell api version', function (): void {
    expect(NavigationHealthCheck::compatibleCapellApiVersion())->toBe('^4.0');
});

it('runs real diagnostics returning check results', function (): void {
    $results = NavigationHealthCheck::runDiagnostics();

    expect($results)->toHaveCount(3)
        ->and($results->every(static fn (mixed $result): bool => $result instanceof DoctorCheckResultData))->toBeTrue();
});

it('passes when the storage table and morph alias are present', function (): void {
    $check = new NavigationHealthCheck;

    expect($check->storageTableCheck()->passed)->toBeTrue()
        ->and($check->modelMorphAliasCheck()->passed)->toBeTrue();
});

it('fails the storage table check when the navigations table is missing', function (): void {
    Schema::drop('navigations');

    $check = new NavigationHealthCheck;

    expect($check->hasStorageTable())->toBeFalse()
        ->and($check->storageTableCheck()->passed)->toBeFalse()
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});

it('fails the morph alias check when the Navigation model is not registered', function (): void {
    Relation::morphMap([], merge: false);

    $check = new NavigationHealthCheck;

    expect($check->hasNavigationMorphAlias())->toBeFalse()
        ->and($check->modelMorphAliasCheck()->passed)->toBeFalse()
        ->and(NavigationHealthCheck::passed())->toBeFalse();
});
