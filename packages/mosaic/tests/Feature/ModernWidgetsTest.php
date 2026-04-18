<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Feature;

use PHPUnit\Framework\TestCase;

class ModernWidgetsTest extends TestCase
{
    /**
     * Test all widget blade components exist
     */
    public function test_all_widget_blade_components_exist()
    {
        $widgets = [
            'hero-banner',
            'card-grid',
            'feature-list',
            'stats-section',
            'testimonials',
            'team-members',
            'pricing-table',
            'faq-section',
            'image-gallery',
            'alternating-content',
            'process-steps',
            'cta-section',
        ];

        $basePath = __DIR__ . '/../../resources/views/components/modern/';

        foreach ($widgets as $widget) {
            $filePath = "{$basePath}{$widget}.blade.php";
            $this->assertTrue(
                file_exists($filePath),
                "Widget component {$widget}.blade.php not found at {$filePath}"
            );
        }
    }

    /**
     * Test design tokens CSS file exists
     */
    public function test_design_tokens_included()
    {
        $this->assertTrue(
            file_exists(__DIR__ . '/../../resources/css/design-tokens.css'),
            'Design tokens CSS file not found'
        );
    }

    /**
     * Test all widget blade components have valid syntax
     */
    public function test_widget_blade_components_have_valid_syntax()
    {
        $widgets = [
            'hero-banner',
            'card-grid',
            'feature-list',
            'stats-section',
            'testimonials',
            'team-members',
            'pricing-table',
            'faq-section',
            'image-gallery',
            'alternating-content',
            'process-steps',
            'cta-section',
        ];

        $basePath = __DIR__ . '/../../resources/views/components/modern/';

        foreach ($widgets as $widget) {
            $filePath = "{$basePath}{$widget}.blade.php";
            $content = file_get_contents($filePath);

            // Basic checks for valid Blade syntax
            $this->assertStringContainsString('@props', $content, "{$widget}: Missing @props declaration");
            $this->assertNotEmpty($content, "{$widget}: File is empty");
            $this->assertStringContainsString('<', $content, "{$widget}: Missing HTML tags");
        }
    }

    /**
     * Test all widget Filament schemas exist
     */
    public function test_all_widget_schemas_exist()
    {
        $schemas = [
            'ModernHeroBannerSchema',
            'ModernCardGridSchema',
            'ModernFeatureListSchema',
            'ModernStatsSectionSchema',
            'ModernTestimonialsSchema',
            'ModernTeamMembersSchema',
            'ModernPricingTableSchema',
            'ModernImageGallerySchema',
            'ModernAlternatingContentSchema',
            'ModernProcessStepsSchema',
            'ModernFaqSchema',
        ];

        $basePath = __DIR__ . '/../../src/Filament/Schemas/Widgets/';

        foreach ($schemas as $schema) {
            $filePath = "{$basePath}{$schema}.php";
            $this->assertTrue(
                file_exists($filePath),
                "Filament schema {$schema}.php not found at {$filePath}"
            );
        }
    }

    /**
     * Test Filament schema files have valid PHP syntax
     */
    public function test_filament_schemas_have_valid_php_syntax()
    {
        $schemas = [
            'ModernHeroBannerSchema',
            'ModernCardGridSchema',
            'ModernFeatureListSchema',
            'ModernStatsSectionSchema',
            'ModernTestimonialsSchema',
            'ModernTeamMembersSchema',
            'ModernPricingTableSchema',
            'ModernImageGallerySchema',
            'ModernAlternatingContentSchema',
            'ModernProcessStepsSchema',
            'ModernFaqSchema',
        ];

        $basePath = __DIR__ . '/../../src/Filament/Schemas/Widgets/';

        foreach ($schemas as $schema) {
            $filePath = "{$basePath}{$schema}.php";
            $content = file_get_contents($filePath);

            // Verify basic class structure
            $this->assertStringContainsString('class ' . $schema, $content, "{$schema}: Class declaration not found");
            $this->assertStringContainsString('declare(strict_types=1)', $content, "{$schema}: Missing declare(strict_types=1)");
        }
    }

    /**
     * Test hero banner component has video prop
     */
    public function test_hero_banner_has_video_prop()
    {
        $content = file_get_contents(__DIR__ . '/../../resources/views/components/modern/hero-banner.blade.php');

        $this->assertStringContainsString('videoUrl', $content, 'Hero banner missing videoUrl prop');
        $this->assertStringContainsString('parallax', $content, 'Hero banner missing parallax prop');
        $this->assertStringContainsString('<video', $content, 'Hero banner missing video element');
    }

    /**
     * Test card grid component has badge support
     */
    public function test_card_grid_has_badge_support()
    {
        $content = file_get_contents(__DIR__ . '/../../resources/views/components/modern/card-grid.blade.php');

        $this->assertStringContainsString('badge', $content, 'Card grid missing badge support');
        $this->assertStringContainsString('hoverEffect', $content, 'Card grid missing hoverEffect prop');
    }

    /**
     * Test feature list component has animation support
     */
    public function test_feature_list_has_animation_support()
    {
        $content = file_get_contents(__DIR__ . '/../../resources/views/components/modern/feature-list.blade.php');

        $this->assertStringContainsString('animation', $content, 'Feature list missing animation prop');
        $this->assertStringContainsString('fade-in', $content, 'Feature list missing fade-in animation');
        $this->assertStringContainsString('animation-delay', $content, 'Feature list missing staggered animation delays');
    }

    /**
     * Test testimonials component has carousel support
     */
    public function test_testimonials_has_carousel_support()
    {
        $content = file_get_contents(__DIR__ . '/../../resources/views/components/modern/testimonials.blade.php');

        $this->assertStringContainsString('displayMode', $content, 'Testimonials missing displayMode prop');
        $this->assertStringContainsString('carousel', $content, 'Testimonials missing carousel support');
    }

    /**
     * Test pricing table component has billing toggle
     */
    public function test_pricing_table_has_billing_toggle()
    {
        $content = file_get_contents(__DIR__ . '/../../resources/views/components/modern/pricing-table.blade.php');

        $this->assertStringContainsString('billingOptions', $content, 'Pricing table missing billingOptions prop');
        $this->assertStringContainsString('toggleBilling', $content, 'Pricing table missing billing toggle function');
    }

    /**
     * Test team members component has social media support
     */
    public function test_team_members_has_social_support()
    {
        $content = file_get_contents(__DIR__ . '/../../resources/views/components/modern/team-members.blade.php');

        $this->assertStringContainsString('social', $content, 'Team members missing social prop');
        $this->assertStringContainsString('tags', $content, 'Team members missing tags prop');
    }

    /**
     * Test FAQ component has category filtering
     */
    public function test_faq_has_category_filtering()
    {
        $content = file_get_contents(__DIR__ . '/../../resources/views/components/modern/faq-section.blade.php');

        $this->assertStringContainsString('categories', $content, 'FAQ missing categories prop');
        $this->assertStringContainsString('filterFaqCategory', $content, 'FAQ missing category filter function');
    }
}
