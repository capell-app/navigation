<?php

declare(strict_types=1);

use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Support\Registry\NavigationHandleRegistry;

it('allows packages and themes to register additional menu handles', function (): void {
    NavigationHandleRegistry::flush();

    NavigationHandleRegistry::register('account-menu', 'Account menu');

    expect(NavigationHandleRegistry::options())
        ->toHaveKey('main')
        ->toHaveKey('footer')
        ->toHaveKey('sub-footer')
        ->toHaveKey('account-menu')
        ->and(NavigationHandleRegistry::label('account-menu'))->toBe('Account menu')
        ->and(NavigationHandleRegistry::label('unknown-menu'))->toBe('unknown-menu');

    NavigationHandleRegistry::flush();
});

it('shows translated labels for every built-in menu handle and item type', function (): void {
    app()->setLocale('en');

    expect(NavigationHandle::Main->getLabel())->toBe('Main menu')
        ->and(NavigationHandle::Footer->getLabel())->toBe('Footer menu')
        ->and(NavigationHandle::SubFooter->getLabel())->toBe('Sub-footer menu')
        ->and(NavigationItemType::Link->getLabel())->toBe('Link')
        ->and(NavigationItemType::Page->getLabel())->toBe('Page')
        ->and(NavigationItemType::Heading->getLabel())->toBe('Heading');
});
