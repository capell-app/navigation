<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

enum HeaderNavigationBreakpoint: string
{
    case Lg = 'lg';
    case Xl = 'xl';

    public function mobileMediaQuery(): string
    {
        return match ($this) {
            self::Lg => '(max-width: 1023px)',
            self::Xl => '(max-width: 1279px)',
        };
    }

    public function desktopMediaQuery(): string
    {
        return match ($this) {
            self::Lg => '(min-width: 1024px)',
            self::Xl => '(min-width: 1280px)',
        };
    }

    public function defaultItemClasses(): string
    {
        return match ($this) {
            self::Lg => 'nav-item font-heading group flex w-full cursor-pointer items-center justify-between gap-x-2 px-6 py-3 text-sm font-medium hover:bg-gray-50 focus-visible:bg-gray-50 lg:!bg-transparent lg:px-4 lg:py-1 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800',
            self::Xl => 'nav-item font-heading group flex w-full cursor-pointer items-center justify-between gap-x-2 px-6 py-3 text-sm font-medium hover:bg-gray-50 focus-visible:bg-gray-50 xl:!bg-transparent xl:px-4 xl:py-1 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800',
        };
    }

    public function menuToggleClasses(): string
    {
        return match ($this) {
            self::Lg => 'absolute top-[max(2vh,14px)] right-4 z-50 flex items-center justify-end gap-x-2 md:gap-x-1 lg:hidden', self::Xl => 'absolute top-[max(2vh,14px)] right-4 z-50 flex items-center justify-end gap-x-2 md:gap-x-1 xl:hidden'
        };
    }

    public function menuWrapperClasses(): string
    {
        return match ($this) {
            self::Lg => 'menu-wrapper relative flex h-full w-full grow justify-center bg-[var(--bg-color-header)] max-lg:fixed max-lg:inset-0 max-lg:z-40 max-lg:h-dvh max-lg:w-screen max-lg:justify-start max-lg:bg-transparent lg:visible lg:w-auto lg:justify-end lg:!bg-transparent', self::Xl => 'menu-wrapper relative flex h-full w-full grow justify-center bg-[var(--bg-color-header)] max-xl:fixed max-xl:inset-0 max-xl:z-40 max-xl:h-dvh max-xl:w-screen max-xl:justify-start max-xl:bg-transparent xl:visible xl:w-auto xl:justify-end xl:!bg-transparent'
        };
    }

    public function mobileInvisibleClass(): string
    {
        return match ($this) {
            self::Lg => 'max-lg:invisible', self::Xl => 'max-xl:invisible'
        };
    }

    public function backdropClasses(): string
    {
        return match ($this) {
            self::Lg => 'menu-backdrop fixed inset-0 z-30 h-dvh w-screen bg-black/65 backdrop-blur-sm lg:hidden', self::Xl => 'menu-backdrop fixed inset-0 z-30 h-dvh w-screen bg-black/65 backdrop-blur-sm xl:hidden'
        };
    }

    public function navbarClasses(): string
    {
        return match ($this) {
            self::Lg => 'navbar top-0 left-0 z-40 flex h-full w-full max-w-md transform flex-col overflow-x-hidden overflow-y-auto border-t border-gray-100 bg-white transition-[translate,visibility] duration-500 ease-in-out max-lg:fixed max-lg:bottom-0 max-lg:h-dvh max-lg:max-w-[22rem] lg:visible lg:static lg:max-w-none lg:translate-x-0 lg:flex-row lg:items-center lg:overflow-visible lg:border-0 lg:bg-transparent lg:transition-none dark:border-gray-700 dark:bg-gray-950 dark:lg:bg-transparent', self::Xl => 'navbar top-0 left-0 z-40 flex h-full w-full max-w-md transform flex-col overflow-x-hidden overflow-y-auto border-t border-gray-100 bg-white transition-[translate,visibility] duration-500 ease-in-out max-xl:fixed max-xl:bottom-0 max-xl:h-dvh max-xl:max-w-[22rem] xl:visible xl:static xl:max-w-none xl:translate-x-0 xl:flex-row xl:items-center xl:overflow-visible xl:border-0 xl:bg-transparent xl:transition-none dark:border-gray-700 dark:bg-gray-950 dark:xl:bg-transparent'
        };
    }

    public function navbarInvisibleClasses(): string
    {
        return match ($this) {
            self::Lg => 'max-lg:invisible max-lg:absolute', self::Xl => 'max-xl:invisible max-xl:absolute'
        };
    }

    public function navbarOpenClasses(): string
    {
        return match ($this) {
            self::Lg => 'max-lg:!visible max-lg:!translate-x-0', self::Xl => 'max-xl:!visible max-xl:!translate-x-0'
        };
    }

    public function navbarClosingClasses(): string
    {
        return match ($this) {
            self::Lg => 'max-lg:!visible max-lg:translate-x-[-100%]', self::Xl => 'max-xl:!visible max-xl:translate-x-[-100%]'
        };
    }

    public function navbarClosedClasses(): string
    {
        return match ($this) {
            self::Lg => 'max-lg:invisible max-lg:translate-x-[-100%]', self::Xl => 'max-xl:invisible max-xl:translate-x-[-100%]'
        };
    }

    public function navItemsClasses(): string
    {
        return match ($this) {
            self::Lg => 'nav-items relative flex w-full flex-col flex-wrap justify-center gap-y-0.5 p-4 pt-6 lg:static lg:w-auto lg:flex-row lg:flex-nowrap lg:items-center lg:gap-1 lg:p-0', self::Xl => 'nav-items relative flex w-full flex-col flex-wrap justify-center gap-y-0.5 p-4 pt-6 xl:static xl:w-auto xl:flex-row xl:flex-nowrap xl:items-center xl:gap-1 xl:p-0'
        };
    }

    public function alignmentClass(string $alignment): string
    {
        return match ($this) {
            self::Lg => match ($alignment) {
                'left' => 'lg:justify-start', 'center' => 'lg:justify-center', default => 'lg:justify-end'
            }, self::Xl => match ($alignment) {
                'left' => 'xl:justify-start', 'center' => 'xl:justify-center', default => 'xl:justify-end'
            }
        };
    }

    public function darkModeWrapperClasses(): string
    {
        return match ($this) {
            self::Lg => 'grid grid-cols-2 items-center justify-between gap-x-2 border-t border-gray-100 p-4 lg:mt-0 lg:ml-auto lg:flex lg:h-auto lg:gap-x-3 lg:divide-none lg:border-0 lg:px-0 lg:py-2 dark:border-gray-700', self::Xl => 'grid grid-cols-2 items-center justify-between gap-x-2 border-t border-gray-100 p-4 xl:mt-0 xl:ml-auto xl:flex xl:h-auto xl:gap-x-3 xl:divide-none xl:border-0 xl:px-0 xl:py-2 dark:border-gray-700'
        };
    }

    public function darkModeButtonClasses(): string
    {
        return match ($this) {
            self::Lg => 'hover:text-primary flex h-auto w-full cursor-pointer justify-between rounded-lg border border-gray-100 px-3 py-3 lg:h-10 lg:w-10 lg:items-center lg:justify-center lg:rounded-full lg:p-0 dark:border-gray-600', self::Xl => 'hover:text-primary flex h-auto w-full cursor-pointer justify-between rounded-lg border border-gray-100 px-3 py-3 xl:h-10 xl:w-10 xl:items-center xl:justify-center xl:rounded-full xl:p-0 dark:border-gray-600'
        };
    }

    public function mobileOnlyClass(): string
    {
        return match ($this) {
            self::Lg => 'lg:hidden', self::Xl => 'xl:hidden'
        };
    }

    public function itemLabelClasses(bool $hidden): string
    {
        return match ($this) {
            self::Lg => $hidden ? 'lg:order-2 lg:sr-only' : 'lg:order-2', self::Xl => $hidden ? 'xl:order-2 xl:sr-only' : 'xl:order-2'
        };
    }

    public function itemIconClasses(): string
    {
        return match ($this) {
            self::Lg => 'h-4 w-4 lg:order-1', self::Xl => 'h-4 w-4 xl:order-1'
        };
    }

    public function dropdownContainerClasses(): string
    {
        return match ($this) {
            self::Lg => 'group flex lg:relative', self::Xl => 'group flex xl:relative'
        };
    }

    public function dropdownWithoutAlpineClasses(): string
    {
        return match ($this) {
            self::Lg => 'capell-navigation-menu-dropdown flex flex-col lg:flex-row', self::Xl => 'capell-navigation-menu-dropdown flex flex-col xl:flex-row'
        };
    }

    public function dropdownChildrenClasses(): string
    {
        return match ($this) {
            self::Lg => 'flex flex-col lg:flex-row', self::Xl => 'flex flex-col xl:flex-row'
        };
    }

    public function dropdownPanelClasses(): string
    {
        return match ($this) {
            self::Lg => 'rounded-xl border border-slate-200 p-2 shadow-xl shadow-slate-900/10 max-lg:inset-0 max-lg:rounded-none max-lg:border-0 max-lg:shadow-none', self::Xl => 'rounded-xl border border-slate-200 p-2 shadow-xl shadow-slate-900/10 max-xl:inset-0 max-xl:rounded-none max-xl:border-0 max-xl:shadow-none'
        };
    }

    public function dropdownWidthClasses(): string
    {
        return match ($this) {
            self::Lg => 'lg:w-max lg:min-w-72', self::Xl => 'xl:w-max xl:min-w-72'
        };
    }

    public function megaDropdownClasses(): string
    {
        return match ($this) {
            self::Lg => 'lg:left-1/2 lg:w-[min(72rem,calc(100vw-2rem))] lg:-translate-x-1/2 lg:p-4', self::Xl => 'xl:left-1/2 xl:w-[min(72rem,calc(100vw-2rem))] xl:-translate-x-1/2 xl:p-4'
        };
    }

    public function hiddenLabelClasses(): string
    {
        return match ($this) {
            self::Lg => 'mr-1 lg:sr-only', self::Xl => 'mr-1 xl:sr-only'
        };
    }

    public function chevronClasses(): string
    {
        return match ($this) {
            self::Lg => '-mr-2 ml-auto h-4 w-4 text-gray-400 group-hover:text-inherit group-focus:text-inherit lg:rotate-90', self::Xl => '-mr-2 ml-auto h-4 w-4 text-gray-400 group-hover:text-inherit group-focus:text-inherit xl:rotate-90'
        };
    }

    public function megaGridClasses(): string
    {
        return match ($this) {
            self::Lg => 'lg:grid lg:gap-3', self::Xl => 'xl:grid xl:gap-3'
        };
    }

    public function megaChildrenGridClasses(): string
    {
        return match ($this) {
            self::Lg => 'lg:grid lg:gap-1', self::Xl => 'xl:grid xl:gap-1'
        };
    }

    public function megaColumnClass(int $columns): string
    {
        return match ($this) {
            self::Lg => match ($columns) {
                1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 4 => 'lg:grid-cols-4', default => 'lg:grid-cols-3'
            }, self::Xl => match ($columns) {
                1 => 'xl:grid-cols-1', 2 => 'xl:grid-cols-2', 4 => 'xl:grid-cols-4', default => 'xl:grid-cols-3'
            }
        };
    }

    public function megaPanelGridClasses(): string
    {
        return match ($this) {
            self::Lg => 'lg:grid-cols-[minmax(12rem,18rem)_1fr]', self::Xl => 'xl:grid-cols-[minmax(12rem,18rem)_1fr]'
        };
    }
}
