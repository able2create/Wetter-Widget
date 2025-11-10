<?php
/**
 * Weather Widget Admin Settings
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
 * Weather Widget Admin Handler
 */
class WeatherAdmin
{
    /**
     * Settings option name
     */
    private const OPTION_NAME = 'weather_widget_settings';

    /**
     * Settings group
     */
    private const SETTINGS_GROUP = 'weather_widget_group';

    /**
     * Settings page slug
     */
    private const PAGE_SLUG = 'weather-widget-settings';

    /**
     * Add admin menu item
     */
    public static function addAdminMenu(): void
    {
        add_options_page(
            'Wetter Widget Einstellungen',
            'Wetter Widget',
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'renderSettingsPage']
        );
    }

    /**
     * Register settings
     */
    public static function registerSettings(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitizeSettings'],
                'default' => self::getDefaultSettings(),
            ]
        );

        add_settings_section(
            'weather_widget_main_section',
            'Haupteinstellungen',
            [self::class, 'renderSectionDescription'],
            self::PAGE_SLUG
        );

        add_settings_field(
            'postal_code',
            'Postleitzahl',
            [self::class, 'renderPostalCodeField'],
            self::PAGE_SLUG,
            'weather_widget_main_section'
        );

        add_settings_field(
            'days_count',
            'Anzahl Tage',
            [self::class, 'renderDaysCountField'],
            self::PAGE_SLUG,
            'weather_widget_main_section'
        );

        add_settings_field(
            'show_icons',
            'Icons anzeigen',
            [self::class, 'renderShowIconsField'],
            self::PAGE_SLUG,
            'weather_widget_main_section'
        );

        add_settings_field(
            'cache_clear',
            'Cache',
            [self::class, 'renderCacheClearField'],
            self::PAGE_SLUG,
            'weather_widget_main_section'
        );
    }

    /**
     * Get default settings
     *
     * @return array
     */
    public static function getDefaultSettings(): array
    {
        return [
            'postal_code' => '',
            'days_count' => 3,
            'show_icons' => true,
        ];
    }

    /**
     * Get current settings
     *
     * @return array
     */
    public static function getSettings(): array
    {
        $settings = get_option(self::OPTION_NAME, self::getDefaultSettings());
        return wp_parse_args($settings, self::getDefaultSettings());
    }

    /**
     * Sanitize settings
     *
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public static function sanitizeSettings(array $input): array
    {
        $sanitized = [];

        // Sanitize postal code (German format: 5 digits)
        if (isset($input['postal_code'])) {
            $postalCode = sanitize_text_field($input['postal_code']);
            $postalCode = preg_replace('/[^0-9]/', '', $postalCode);
            $sanitized['postal_code'] = $postalCode;

            if (strlen($postalCode) !== 5) {
                add_settings_error(
                    self::OPTION_NAME,
                    'invalid_postal_code',
                    'Bitte geben Sie eine gültige 5-stellige Postleitzahl ein.',
                    'error'
                );
            }
        }

        // Sanitize days count (1-7)
        if (isset($input['days_count'])) {
            $daysCount = (int) $input['days_count'];
            $sanitized['days_count'] = max(1, min(7, $daysCount));
        }

        // Sanitize show icons checkbox
        $sanitized['show_icons'] = !empty($input['show_icons']);

        return $sanitized;
    }

    /**
     * Render section description
     */
    public static function renderSectionDescription(): void
    {
        echo '<p>Konfigurieren Sie die Einstellungen für das Wetter Widget.</p>';
    }

    /**
     * Render postal code field
     */
    public static function renderPostalCodeField(): void
    {
        $settings = self::getSettings();
        $value = esc_attr($settings['postal_code']);
        ?>
        <input
            type="text"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>[postal_code]"
            value="<?php echo $value; ?>"
            class="regular-text"
            placeholder="z.B. 10115"
            pattern="[0-9]{5}"
            maxlength="5"
            required
        />
        <p class="description">
            Geben Sie eine deutsche Postleitzahl ein (5 Ziffern).
        </p>
        <?php
    }

    /**
     * Render days count field
     */
    public static function renderDaysCountField(): void
    {
        $settings = self::getSettings();
        $value = (int) $settings['days_count'];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[days_count]">
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <option value="<?php echo $i; ?>" <?php selected($value, $i); ?>>
                    <?php echo $i; ?> <?php echo $i === 1 ? 'Tag' : 'Tage'; ?>
                </option>
            <?php endfor; ?>
        </select>
        <p class="description">
            Anzahl der Tage für die Wettervorhersage (1-7).
        </p>
        <?php
    }

    /**
     * Render show icons field
     */
    public static function renderShowIconsField(): void
    {
        $settings = self::getSettings();
        $checked = !empty($settings['show_icons']);
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME); ?>[show_icons]"
                value="1"
                <?php checked($checked); ?>
            />
            Wetter-Icons im Frontend anzeigen
        </label>
        <?php
    }

    /**
     * Render cache clear field
     */
    public static function renderCacheClearField(): void
    {
        // Handle cache clear
        if (isset($_POST['weather_widget_clear_cache']) && check_admin_referer('weather_widget_clear_cache')) {
            WeatherAPI::clearCache();
            add_settings_error(
                self::OPTION_NAME,
                'cache_cleared',
                'Cache erfolgreich geleert.',
                'success'
            );
        }
        ?>
        <form method="post" style="display: inline;">
            <?php wp_nonce_field('weather_widget_clear_cache'); ?>
            <button type="submit" name="weather_widget_clear_cache" class="button button-secondary">
                Cache jetzt leeren
            </button>
        </form>
        <p class="description">
            Wetterinformationen werden für 30 Minuten zwischengespeichert. Leeren Sie den Cache, um neue Daten abzurufen.
        </p>
        <?php
    }

    /**
     * Render settings page
     */
    public static function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if settings have been saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                self::OPTION_NAME,
                'weather_widget_message',
                'Einstellungen gespeichert.',
                'success'
            );
        }

        settings_errors(self::OPTION_NAME);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Verwendung</h2>
                <p>Sie können das Wetter Widget auf verschiedene Arten einbinden:</p>

                <h3>Shortcode (in Posts/Pages):</h3>
                <code>[weather_widget]</code>
                <p class="description">Fügen Sie diesen Shortcode in jeden Beitrag oder jede Seite ein.</p>

                <h3>Template Tag (in PHP-Dateien):</h3>
                <code>&lt;?php \WeatherWidget\display_weather_widget(); ?&gt;</code>
                <p class="description">Verwenden Sie diese Funktion in Ihren Theme-Templates.</p>
            </div>

            <form action="options.php" method="post" style="margin-top: 20px;">
                <?php
                settings_fields(self::SETTINGS_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button('Einstellungen speichern');
                ?>
            </form>
        </div>
        <?php
    }
}
