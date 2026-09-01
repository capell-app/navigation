<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * The editor-facing intent behind a navigation.
 *
 * The first three purposes derive a built-in {@see NavigationHandle} key, so
 * an editor never has to translate "main" into "the menu in the site header".
 * Custom keeps the full key and blueprint controls for themes that request a
 * menu under their own key.
 */
enum NavigationPurpose: string implements HasDescription, HasLabel
{
    case Main = 'main';

    case Footer = 'footer';

    case SubFooter = 'sub-footer';

    case Custom = 'custom';

    /**
     * Resolve the purpose that matches a stored navigation key.
     */
    public static function fromKey(?string $key): self
    {
        $normalizedKey = $key === null ? '' : trim($key);

        return match ($normalizedKey) {
            NavigationHandle::Main->value => self::Main,
            NavigationHandle::Footer->value => self::Footer,
            NavigationHandle::SubFooter->value => self::SubFooter,
            default => self::Custom,
        };
    }

    /**
     * The built-in key this purpose derives, or null when the editor supplies
     * their own key.
     */
    public function handle(): ?NavigationHandle
    {
        return match ($this) {
            self::Main => NavigationHandle::Main,
            self::Footer => NavigationHandle::Footer,
            self::SubFooter => NavigationHandle::SubFooter,
            self::Custom => null,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Main => (string) __('capell-navigation::generic.purpose_main'),
            self::Footer => (string) __('capell-navigation::generic.purpose_footer'),
            self::SubFooter => (string) __('capell-navigation::generic.purpose_sub_footer'),
            self::Custom => (string) __('capell-navigation::generic.purpose_custom'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Main => (string) __('capell-navigation::generic.purpose_main_description'),
            self::Footer => (string) __('capell-navigation::generic.purpose_footer_description'),
            self::SubFooter => (string) __('capell-navigation::generic.purpose_sub_footer_description'),
            self::Custom => (string) __('capell-navigation::generic.purpose_custom_description'),
        };
    }

    /**
     * The default navigation name suggested for this purpose.
     */
    public function defaultName(): ?string
    {
        return match ($this) {
            self::Main => (string) __('capell-navigation::generic.main_navigation'),
            self::Footer => (string) __('capell-navigation::generic.footer_navigation'),
            self::SubFooter => (string) __('capell-navigation::generic.sub_footer_navigation'),
            self::Custom => null,
        };
    }
}
