<?php
/**
 * Weather Widget Loader
 *
 * Include this file in your theme's functions.php to activate the weather widget.
 * Example: require_once get_template_directory() . '/inc/weather-widget-loader.php';
 *
 * @package WeatherWidget
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load Weather Widget
 */
function weather_widget_load(): void
{
    // Define path to weather widget
    $widgetPath = get_template_directory() . '/inc/weather-widget/weather-widget.php';

    // Check if file exists
    if (!file_exists($widgetPath)) {
        // Try child theme directory
        $widgetPath = get_stylesheet_directory() . '/inc/weather-widget/weather-widget.php';

        if (!file_exists($widgetPath)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                echo 'Weather Widget konnte nicht geladen werden. Bitte stellen Sie sicher, dass die Dateien im /inc/weather-widget/ Verzeichnis vorhanden sind.';
                echo '</p></div>';
            });
            return;
        }
    }

    // Load the widget
    require_once $widgetPath;

    // Add action to show admin notice on successful load (only for admins)
    if (is_admin() && current_user_can('manage_options')) {
        add_action('admin_notices', function () {
            static $shown = false;
            if ($shown) {
                return;
            }
            $shown = true;

            // Check if settings are configured
            $settings = get_option('weather_widget_settings', []);

            if (empty($settings['postal_code'])) {
                echo '<div class="notice notice-info is-dismissible"><p>';
                echo 'Weather Widget wurde geladen. Bitte konfigurieren Sie die Einstellungen unter ';
                echo '<a href="' . admin_url('options-general.php?page=weather-widget-settings') . '">Einstellungen > Wetter Widget</a>.';
                echo '</p></div>';
            }
        });
    }
}

// Initialize the widget
add_action('after_setup_theme', 'weather_widget_load', 10);

/**
 * Add weather widget to WordPress widgets
 * (Optional: for sidebar integration)
 */
class Weather_Widget_Sidebar extends \WP_Widget
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

        // Display weather widget
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
 * Register the widget
 */
function weather_widget_register_sidebar_widget(): void
{
    register_widget('Weather_Widget_Sidebar');
}
add_action('widgets_init', 'weather_widget_register_sidebar_widget');
