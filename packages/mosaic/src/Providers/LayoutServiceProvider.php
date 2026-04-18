<?php

declare(strict_types=1);

namespace Capell\Mosaic\Providers;

/**
 * Backward-compatible alias. Packages that depend on the old "layout" name
 * (e.g. capell-app/hero) reference this class — it delegates everything to
 * MosaicServiceProvider.
 */
class LayoutServiceProvider extends MosaicServiceProvider {}
