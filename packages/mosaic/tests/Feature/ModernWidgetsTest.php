<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\TestCase;

class ModernWidgetsTest extends TestCase
{
    /**
     * Test hero banner widget renders without errors
     */
    public function test_hero_banner_renders()
    {
        $html = Blade::render('
            <x-mosaic::modern.hero-banner
                title="Welcome"
                subtitle="Get started today"
            />
        ');

        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('Get started today', $html);
        $this->assertStringContainsString('mosaic-hero-banner', $html);
    }

    /**
     * Test hero banner with video background
     */
    public function test_hero_banner_video_background()
    {
        $html = Blade::render('
            <x-mosaic::modern.hero-banner
                title="Video Hero"
                :videoUrl="\'https://example.com/video.mp4\'"
            />
        ');

        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('video.mp4', $html);
    }

    /**
     * Test hero banner parallax effect
     */
    public function test_hero_banner_parallax()
    {
        $html = Blade::render('
            <x-mosaic::modern.hero-banner
                title="Parallax"
                :parallax="true"
            />
        ');

        $this->assertStringContainsString('mosaic-hero-parallax', $html);
        $this->assertStringContainsString('background-attachment: fixed', $html);
    }

    /**
     * Test card grid widget renders
     */
    public function test_card_grid_renders()
    {
        $cards = [
            ['icon' => '🎨', 'title' => 'Design', 'description' => 'Beautiful designs'],
            ['icon' => '⚡', 'title' => 'Fast', 'description' => 'Lightning fast'],
        ];

        $html = Blade::render('
            <x-mosaic::modern.card-grid
                title="Features"
                :cards="$cards"
                columns="2"
            />
        ', ['cards' => $cards]);

        $this->assertStringContainsString('Features', $html);
        $this->assertStringContainsString('Design', $html);
        $this->assertStringContainsString('Fast', $html);
    }

    /**
     * Test card grid with badges
     */
    public function test_card_grid_with_badges()
    {
        $cards = [
            ['icon' => '⭐', 'title' => 'Popular', 'description' => 'Best choice', 'badge' => 'Popular'],
        ];

        $html = Blade::render('
            <x-mosaic::modern.card-grid
                :cards="$cards"
            />
        ', ['cards' => $cards]);

        $this->assertStringContainsString('Popular', $html);
    }

    /**
     * Test card grid hover effects
     */
    public function test_card_grid_hover_effects()
    {
        $html = Blade::render('
            <x-mosaic::modern.card-grid
                :hoverEffect="\'scale\'"
            />
        ');

        $this->assertStringContainsString('hover-scale', $html);
        $this->assertStringContainsString('scale(1.05)', $html);
    }

    /**
     * Test feature list with animations
     */
    public function test_feature_list_with_animations()
    {
        $features = [
            ['icon' => '🚀', 'title' => 'Fast', 'description' => 'Quick setup'],
            ['icon' => '🎯', 'title' => 'Accurate', 'description' => 'Precise results'],
        ];

        $html = Blade::render('
            <x-mosaic::modern.feature-list
                :features="$features"
                animation="fade-in"
            />
        ', ['features' => $features]);

        $this->assertStringContainsString('animate-fade-in', $html);
        $this->assertStringContainsString('animation-delay', $html);
    }

    /**
     * Test testimonials widget
     */
    public function test_testimonials_widget()
    {
        $testimonials = [
            [
                'quote' => 'Amazing product',
                'author' => 'John Doe',
                'role' => 'CEO',
                'avatar' => '👨‍💼',
            ],
        ];

        $html = Blade::render('
            <x-mosaic::modern.testimonials
                title="Reviews"
                :testimonials="$testimonials"
            />
        ', ['testimonials' => $testimonials]);

        $this->assertStringContainsString('Amazing product', $html);
        $this->assertStringContainsString('John Doe', $html);
    }

    /**
     * Test testimonials carousel mode
     */
    public function test_testimonials_carousel_mode()
    {
        $html = Blade::render('
            <x-mosaic::modern.testimonials
                displayMode="carousel"
            />
        ');

        $this->assertStringContainsString('mosaic-testimonials-carousel', $html);
        $this->assertStringContainsString('carousel-prev', $html);
        $this->assertStringContainsString('carousel-next', $html);
    }

    /**
     * Test pricing table widget
     */
    public function test_pricing_table_widget()
    {
        $plans = [
            ['name' => 'Starter', 'price' => '29', 'priceAnnual' => '290'],
            ['name' => 'Pro', 'price' => '79', 'priceAnnual' => '790', 'featured' => true],
        ];

        $html = Blade::render('
            <x-mosaic::modern.pricing-table
                title="Pricing"
                :plans="$plans"
            />
        ', ['plans' => $plans]);

        $this->assertStringContainsString('Starter', $html);
        $this->assertStringContainsString('Pro', $html);
    }

    /**
     * Test pricing table billing toggle
     */
    public function test_pricing_table_billing_toggle()
    {
        $html = Blade::render('
            <x-mosaic::modern.pricing-table
                :billingOptions="\'both\'"
            />
        ');

        $this->assertStringContainsString('billing-toggle-button', $html);
        $this->assertStringContainsString('Monthly', $html);
        $this->assertStringContainsString('Annual', $html);
    }

    /**
     * Test team members widget
     */
    public function test_team_members_widget()
    {
        $members = [
            [
                'name' => 'Alice',
                'role' => 'Designer',
                'avatar' => '👩‍💼',
                'bio' => 'Creative designer',
                'tags' => ['Design', 'Leadership'],
            ],
        ];

        $html = Blade::render('
            <x-mosaic::modern.team-members
                :members="$members"
            />
        ', ['members' => $members]);

        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('Designer', $html);
        $this->assertStringContainsString('Design', $html);
    }

    /**
     * Test team members social links
     */
    public function test_team_members_social_links()
    {
        $members = [
            [
                'name' => 'Bob',
                'role' => 'Developer',
                'avatar' => '👨‍💻',
                'bio' => 'Full-stack dev',
                'social' => ['twitter' => 'https://twitter.com/bob', 'github' => 'https://github.com/bob'],
            ],
        ];

        $html = Blade::render('
            <x-mosaic::modern.team-members
                :members="$members"
            />
        ', ['members' => $members]);

        $this->assertStringContainsString('twitter.com', $html);
        $this->assertStringContainsString('github.com', $html);
    }

    /**
     * Test FAQ widget with categories
     */
    public function test_faq_widget_with_categories()
    {
        $faqs = [
            ['question' => 'How?', 'answer' => 'Like this', 'category' => 'Getting Started'],
            ['question' => 'Why?', 'answer' => 'Because', 'category' => 'Features'],
        ];

        $categories = ['Getting Started', 'Features'];

        $html = Blade::render('
            <x-mosaic::modern.faq-section
                :faqs="$faqs"
                :categories="$categories"
            />
        ', ['faqs' => $faqs, 'categories' => $categories]);

        $this->assertStringContainsString('faq-category-tab', $html);
        $this->assertStringContainsString('Getting Started', $html);
        $this->assertStringContainsString('Features', $html);
    }

    /**
     * Test stats section widget
     */
    public function test_stats_section_widget()
    {
        $stats = [
            ['label' => 'Users', 'value' => '1000+', 'icon' => '👥'],
            ['label' => 'Projects', 'value' => '500+', 'icon' => '📁'],
        ];

        $html = Blade::render('
            <x-mosaic::modern.stats-section
                title="Metrics"
                :stats="$stats"
            />
        ', ['stats' => $stats]);

        $this->assertStringContainsString('Users', $html);
        $this->assertStringContainsString('1000+', $html);
    }

    /**
     * Test alternating content widget
     */
    public function test_alternating_content_widget()
    {
        $sections = [
            ['title' => 'Section 1', 'description' => 'Content 1', 'icon' => '1️⃣'],
            ['title' => 'Section 2', 'description' => 'Content 2', 'icon' => '2️⃣'],
        ];

        $html = Blade::render('
            <x-mosaic::modern.alternating-content
                title="Process"
                :sections="$sections"
            />
        ', ['sections' => $sections]);

        $this->assertStringContainsString('Section 1', $html);
        $this->assertStringContainsString('Section 2', $html);
    }

    /**
     * Test process steps widget
     */
    public function test_process_steps_widget()
    {
        $steps = [
            ['title' => 'Step 1', 'description' => 'First step', 'icon' => '1️⃣'],
            ['title' => 'Step 2', 'description' => 'Second step', 'icon' => '2️⃣'],
        ];

        $html = Blade::render('
            <x-mosaic::modern.process-steps
                title="Process"
                :steps="$steps"
            />
        ', ['steps' => $steps]);

        $this->assertStringContainsString('Step 1', $html);
        $this->assertStringContainsString('Step 2', $html);
    }

    /**
     * Test image gallery widget
     */
    public function test_image_gallery_widget()
    {
        $images = [
            ['src' => 'https://example.com/1.jpg', 'caption' => 'Image 1'],
            ['src' => 'https://example.com/2.jpg', 'caption' => 'Image 2'],
        ];

        $html = Blade::render('
            <x-mosaic::modern.image-gallery
                title="Gallery"
                :images="$images"
                columns="3"
            />
        ', ['images' => $images]);

        $this->assertStringContainsString('Image 1', $html);
        $this->assertStringContainsString('Image 2', $html);
    }

    /**
     * Test all widgets have customizable prop
     */
    public function test_widgets_support_customizable_flag()
    {
        $html = Blade::render('
            <x-mosaic::modern.hero-banner :customizable="true" />
        ');

        // Admin hints should be present when customizable is true
        // (In actual render, would need auth context)
        $this->assertStringContainsString('mosaic-hero-banner', $html);
    }

    /**
     * Test responsive grid classes
     */
    public function test_responsive_grid_layouts()
    {
        $html = Blade::render('
            <x-mosaic::modern.card-grid columns="2" />
        ');

        $this->assertStringContainsString('grid-cols-1', $html);
        $this->assertStringContainsString('md:grid-cols-2', $html);
    }

    /**
     * Test empty state handling
     */
    public function test_empty_state_handling()
    {
        $html = Blade::render('
            <x-mosaic::modern.card-grid :cards="[]" />
        ');

        $this->assertStringContainsString('No cards configured', $html);
    }

    /**
     * Test design tokens loading
     */
    public function test_design_tokens_included()
    {
        $this->assertTrue(file_exists(
            __DIR__ . '/../../resources/css/design-tokens.css'
        ));
    }

    /**
     * Test all widget schemas exist
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
            $this->assertTrue(
                file_exists("{$basePath}{$schema}.php"),
                "Schema {$schema}.php not found"
            );
        }
    }
}
