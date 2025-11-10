<?php
/**
 * Weather Widget Frontend Display
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
 * Weather Widget Frontend Handler
 */
class WeatherFrontend
{
    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function renderShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'postal_code' => '',
            'days' => '',
            'show_icons' => '',
        ], $atts, 'weather_widget');

        // Get settings
        $settings = WeatherAdmin::getSettings();

        // Override with shortcode attributes if provided
        $postalCode = !empty($atts['postal_code']) ? $atts['postal_code'] : $settings['postal_code'];
        $days = !empty($atts['days']) ? (int) $atts['days'] : (int) $settings['days_count'];
        $showIcons = !empty($atts['show_icons']) ? (bool) $atts['show_icons'] : (bool) $settings['show_icons'];

        // Validate postal code
        if (empty($postalCode) || strlen($postalCode) !== 4) {
            return '<div class="weather-widget weather-widget--error">
                <p>Bitte konfigurieren Sie eine gültige Postleitzahl in den Einstellungen.</p>
            </div>';
        }

        return self::render($postalCode, $days, $showIcons);
    }

    /**
     * Render weather widget
     *
     * @param string $postalCode Postal code
     * @param int $days Number of days
     * @param bool $showIcons Show icons flag
     * @return string HTML output
     */
    public static function render(string $postalCode, int $days, bool $showIcons): string
    {
        // Get coordinates from postal code
        $location = WeatherAPI::getCoordinatesFromPostalCode($postalCode);

        if ($location === null) {
            return '<div class="weather-widget weather-widget--error">
                <p>Standort konnte nicht gefunden werden. Bitte überprüfen Sie die Postleitzahl.</p>
            </div>';
        }

        // Get weather data
        $weatherData = WeatherAPI::getWeatherData($location['lat'], $location['lon'], $days);

        if ($weatherData === null) {
            return '<div class="weather-widget weather-widget--error">
                <p>Wetterdaten konnten nicht abgerufen werden. Bitte versuchen Sie es später erneut.</p>
            </div>';
        }

        // Start output buffering
        ob_start();
        ?>
        <div class="weather-widget" data-location="<?php echo esc_attr($location['name']); ?>">
            <div class="weather-widget__header">
                <h3 class="weather-widget__title">
                    Wetter für <?php echo esc_html($location['name']); ?>
                </h3>
                <p class="weather-widget__subtitle">
                    <?php echo esc_html(date_i18n('j. F Y', current_time('timestamp'))); ?>
                </p>
            </div>

            <div class="weather-widget__current">
                <?php if ($showIcons): ?>
                    <div class="weather-widget__icon">
                        <?php echo self::getWeatherIcon($weatherData['current']['weathercode']); ?>
                    </div>
                <?php endif; ?>
                <div class="weather-widget__current-temp">
                    <span class="weather-widget__temp-value">
                        <?php echo round($weatherData['current']['temperature']); ?>
                    </span>
                    <span class="weather-widget__temp-unit">°C</span>
                </div>
                <div class="weather-widget__current-desc">
                    <p class="weather-widget__description">
                        <?php echo esc_html(WeatherAPI::getWeatherDescription((int) $weatherData['current']['weathercode'])); ?>
                    </p>
                    <p class="weather-widget__wind">
                        Wind: <?php echo round($weatherData['current']['windspeed']); ?> km/h
                    </p>
                </div>
            </div>

            <?php if (count($weatherData['daily']) > 0): ?>
                <div class="weather-widget__forecast">
                    <?php foreach ($weatherData['daily'] as $day): ?>
                        <div class="weather-widget__day">
                            <div class="weather-widget__day-name">
                                <?php echo esc_html(self::formatDayName($day['date'])); ?>
                            </div>
                            <?php if ($showIcons): ?>
                                <div class="weather-widget__day-icon">
                                    <?php echo self::getWeatherIcon((int) $day['weathercode'], true); ?>
                                </div>
                            <?php endif; ?>
                            <div class="weather-widget__day-temps">
                                <span class="weather-widget__temp-max">
                                    <?php echo round($day['temp_max']); ?>°
                                </span>
                                <span class="weather-widget__temp-min">
                                    <?php echo round($day['temp_min']); ?>°
                                </span>
                            </div>
                            <?php if ($day['precipitation'] > 0): ?>
                                <div class="weather-widget__day-rain">
                                    <?php echo round($day['precipitation'], 1); ?> mm
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format day name
     *
     * @param string $date Date string (YYYY-MM-DD)
     * @return string Formatted day name
     */
    private static function formatDayName(string $date): string
    {
        $timestamp = strtotime($date);
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');

        if ($timestamp === $today) {
            return 'Heute';
        } elseif ($timestamp === $tomorrow) {
            return 'Morgen';
        } else {
            return date_i18n('D', $timestamp);
        }
    }

    /**
     * Get SVG icon for weather condition
     *
     * @param int $weatherCode WMO weather code
     * @param bool $small Use small icon
     * @return string SVG markup
     */
    private static function getWeatherIcon(int $weatherCode, bool $small = false): string
    {
        $iconType = WeatherAPI::getWeatherIcon($weatherCode);
        $size = $small ? '32' : '64';

        $icons = [
            'clear' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',

            'cloudy' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',

            'rain' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="19" x2="8" y2="21"/><line x1="8" y1="13" x2="8" y2="15"/><line x1="16" y1="19" x2="16" y2="21"/><line x1="16" y1="13" x2="16" y2="15"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="12" y1="15" x2="12" y2="17"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>',

            'drizzle' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="19" x2="8" y2="21"/><line x1="16" y1="19" x2="16" y2="21"/><line x1="12" y1="21" x2="12" y2="23"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>',

            'snow' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 17.58A5 5 0 0 0 18 8h-1.26A8 8 0 1 0 4 16.25"/><line x1="8" y1="16" x2="8.01" y2="16"/><line x1="8" y1="20" x2="8.01" y2="20"/><line x1="12" y1="18" x2="12.01" y2="18"/><line x1="12" y1="22" x2="12.01" y2="22"/><line x1="16" y1="16" x2="16.01" y2="16"/><line x1="16" y1="20" x2="16.01" y2="20"/></svg>',

            'thunderstorm' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 16.9A5 5 0 0 0 18 7h-1.26a8 8 0 1 0-11.62 9"/><polyline points="13 11 9 17 15 17 11 23"/></svg>',

            'fog' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 15h18"/><path d="M3 9h18"/><path d="M3 12h18"/></svg>',

            'showers' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="19" x2="8" y2="21"/><line x1="8" y1="13" x2="8" y2="15"/><line x1="16" y1="19" x2="16" y2="21"/><line x1="16" y1="13" x2="16" y2="15"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="12" y1="15" x2="12" y2="17"/><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/></svg>',

            'snow-showers' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 17.58A5 5 0 0 0 18 8h-1.26A8 8 0 1 0 4 16.25"/><line x1="8" y1="16" x2="8.01" y2="16"/><line x1="8" y1="20" x2="8.01" y2="20"/><line x1="12" y1="18" x2="12.01" y2="18"/><line x1="12" y1="22" x2="12.01" y2="22"/><line x1="16" y1="16" x2="16.01" y2="16"/><line x1="16" y1="20" x2="16.01" y2="20"/></svg>',
        ];

        return $icons[$iconType] ?? $icons['cloudy'];
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueueAssets(): void
    {
        // Enqueue inline CSS for better performance
        wp_register_style('weather-widget', false);
        wp_enqueue_style('weather-widget');
        wp_add_inline_style('weather-widget', self::getCriticalCSS());
    }

    /**
     * Get critical CSS
     *
     * @return string CSS content
     */
    private static function getCriticalCSS(): string
    {
        return file_get_contents(__DIR__ . '/weather-widget.css');
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
