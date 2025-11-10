<?php
/**
 * Plugin Name: Wetter Widget
 * Plugin URI: https://github.com/able2create/Wetter-Widget
 * Description: Ein minimalistisches Wetter-Widget für WordPress mit Open-Meteo API Integration
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: able2create
 * Author URI: https://github.com/able2create
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wetter-widget
 * Domain Path: /languages
 *
 * @package WetterWidget
 */

declare(strict_types=1);

namespace WeatherWidget;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Weather Widget Plugin Class
 */
final class WeatherWidgetPlugin
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
     * Plugin directory path
     */
    private string $pluginDir;

    /**
     * Plugin URL
     */
    private string $pluginUrl;

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
        $this->pluginDir = plugin_dir_path(__FILE__);
        $this->pluginUrl = plugin_dir_url(__FILE__);

        $this->loadDependencies();
        $this->initHooks();
    }

    /**
     * Load required files
     */
    private function loadDependencies(): void
    {
        require_once $this->pluginDir . 'inc/weather-api.php';
        require_once $this->pluginDir . 'inc/weather-admin.php';
        require_once $this->pluginDir . 'inc/weather-frontend.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void
    {
        // Plugin activation/deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Initialize admin settings
        add_action('admin_menu', [WeatherAdmin::class, 'addAdminMenu']);
        add_action('admin_init', [WeatherAdmin::class, 'registerSettings']);

        // Initialize frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueueConditionalAssets']);
        add_shortcode('weather_widget', [WeatherFrontend::class, 'renderShortcode']);

        // Register widget
        add_action('widgets_init', [$this, 'registerWidget']);

        // Add settings link in plugin list
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'addSettingsLink']);
    }

    /**
     * Plugin activation
     */
    public function activate(): void
    {
        // Set default options
        $defaultSettings = WeatherAdmin::getDefaultSettings();
        if (get_option('weather_widget_settings') === false) {
            add_option('weather_widget_settings', $defaultSettings);
        }

        // Clear any existing caches
        WeatherAPI::clearCache();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(): void
    {
        // Clear all caches
        WeatherAPI::clearCache();
    }

    /**
     * Conditionally enqueue assets only when widget is active
     */
    public function enqueueConditionalAssets(): void
    {
        global $post;

        $shouldLoad = false;

        if ($post && has_shortcode($post->post_content, 'weather_widget')) {
            $shouldLoad = true;
        }

        // Allow themes to force loading via filter
        $shouldLoad = apply_filters('weather_widget_load_assets', $shouldLoad);

        if ($shouldLoad) {
            WeatherFrontend::enqueueAssets($this->pluginDir);
        }
    }

    /**
     * Register sidebar widget
     */
    public function registerWidget(): void
    {
        register_widget(WeatherWidgetSidebar::class);
    }

    /**
     * Add settings link to plugin actions
     */
    public function addSettingsLink(array $links): array
    {
        $settingsLink = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=weather-widget-settings'),
            __('Einstellungen', 'wetter-widget')
        );
        array_unshift($links, $settingsLink);
        return $links;
    }

    /**
     * Get plugin version
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get plugin directory path
     */
    public function getPluginDir(): string
    {
        return $this->pluginDir;
    }

    /**
     * Get plugin URL
     */
    public function getPluginUrl(): string
    {
        return $this->pluginUrl;
    }
}

/**
 * WordPress Sidebar Widget Class
 */
class WeatherWidgetSidebar extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'weather_widget_sidebar',
            'Wetter Widget',
            ['description' => 'Zeigt die aktuelle Wettervorhersage an']
        );
    }

    public function widget($args, $instance): void
    {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        echo do_shortcode('[weather_widget]');

        echo $args['after_widget'];
    }

    public function form($instance): void
    {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                Titel:
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                type="text"
                value="<?php echo esc_attr($title); ?>"
            />
        </p>
        <p class="description">
            Konfigurieren Sie die Wettereinstellungen unter Einstellungen > Wetter Widget.
        </p>
        <?php
    }

    public function update($new_instance, $old_instance): array
    {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * Template tag function for use in themes
 *
 * @param array $args Optional arguments
 */
function display_weather_widget(array $args = []): void
{
    $settings = WeatherAdmin::getSettings();

    $postalCode = $args['postal_code'] ?? $settings['postal_code'];
    $days = $args['days'] ?? $settings['days_count'];
    $showIcons = $args['show_icons'] ?? $settings['show_icons'];

    echo WeatherFrontend::render($postalCode, (int) $days, (bool) $showIcons);
}

// Initialize the plugin
WeatherWidgetPlugin::getInstance();
