<?php
/**
 * Weather Widget Main File
 *
 * @package WeatherWidget
 * @since 1.0.0
 */

declare(strict_types=1);

namespace WeatherWidget;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Weather Widget Class
 */
final class WeatherWidget
{
    /**
     * Plugin version
     */
    private const VERSION = '1.0.0';

    /**
     * Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->loadDependencies();
        $this->initHooks();
    }

    /**
     * Load required files
     */
    private function loadDependencies(): void
    {
        $basePath = __DIR__;

        require_once $basePath . '/weather-api.php';
        require_once $basePath . '/weather-admin.php';
        require_once $basePath . '/weather-frontend.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void
    {
        // Initialize admin settings
        add_action('admin_menu', [WeatherAdmin::class, 'addAdminMenu']);
        add_action('admin_init', [WeatherAdmin::class, 'registerSettings']);

        // Initialize frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueueConditionalAssets']);
        add_shortcode('weather_widget', [WeatherFrontend::class, 'renderShortcode']);
    }

    /**
     * Conditionally enqueue assets only when widget is active
     */
    public function enqueueConditionalAssets(): void
    {
        global $post;

        // Check if shortcode is present in content or if we should load globally
        $shouldLoad = false;

        if ($post && has_shortcode($post->post_content, 'weather_widget')) {
            $shouldLoad = true;
        }

        // Allow themes to force loading via filter
        $shouldLoad = apply_filters('weather_widget_load_assets', $shouldLoad);

        if ($shouldLoad) {
            WeatherFrontend::enqueueAssets();
        }
    }

    /**
     * Get plugin version
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }
}

// Initialize the plugin
WeatherWidget::getInstance();
