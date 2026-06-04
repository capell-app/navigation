<?php

declare(strict_types=1);

return [
    'footer_navigation' => 'Footer navigation',
    'health_header_render_hook_failed' => 'The foundation header navigation render hook is not registered.',
    'health_header_render_hook_label' => 'Navigation header render hook',
    'health_header_render_hook_passed' => 'The foundation header navigation render hook is registered.',
    'health_header_render_hook_remediation' => 'Ensure NavigationServiceProvider registers the foundation header navigation render hook.',
    'health_main_navigation_coverage_failed' => 'Sites missing a main navigation: :sites.',
    'health_main_navigation_coverage_label' => 'Navigation main menu coverage',
    'health_main_navigation_coverage_missing_tables' => 'Navigation main menu coverage could not be checked because required tables are missing.',
    'health_main_navigation_coverage_missing_tables_remediation' => 'Run the Capell migrations before checking navigation main menu coverage.',
    'health_main_navigation_coverage_passed' => 'Every site can resolve a main navigation.',
    'health_main_navigation_coverage_remediation' => 'Create a main navigation for each listed site, or create one global main navigation.',
    'health_morph_alias_failed' => 'The Navigation model is not registered in the morph map.',
    'health_morph_alias_label' => 'Navigation model morph alias',
    'health_morph_alias_passed' => 'The Navigation model is registered in the morph map.',
    'health_morph_alias_remediation' => 'Ensure NavigationServiceProvider registers the Navigation model via CapellCore::registerModels().',
    'health_page_reference_integrity_failed' => 'Navigation has :count orphaned page reference(s).',
    'health_page_reference_integrity_label' => 'Navigation page reference integrity',
    'health_page_reference_integrity_missing_table' => 'Navigation page reference integrity could not be checked because the navigation_page_references table is missing.',
    'health_page_reference_integrity_missing_table_remediation' => 'Run the Capell migrations to create the navigation page references table.',
    'health_page_reference_integrity_passed' => 'Navigation page references point at existing pageable records.',
    'health_page_reference_integrity_remediation' => 'Re-save affected navigations or run the navigation page reference sync command.',
    'health_storage_tables_failed' => 'Missing tables: :tables.',
    'health_storage_tables_label' => 'Navigation storage tables',
    'health_storage_tables_passed' => 'The navigations and navigation_page_references tables are present.',
    'health_storage_tables_remediation' => 'Run the Capell migrations to create the navigation storage tables.',
    'main_navigation' => 'Main navigation',
    'navigation' => 'Navigation',
    'sub_footer_navigation' => 'Footer utility navigation',
];
