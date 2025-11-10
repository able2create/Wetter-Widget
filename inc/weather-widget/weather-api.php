<?php
/**
 * Weather API Integration
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
 * Weather API Handler
 */
class WeatherAPI
{
    /**
     * Cache duration in seconds (30 minutes)
     */
    private const CACHE_DURATION = 30 * MINUTE_IN_SECONDS;

    /**
     * Open-Meteo API endpoints
     */
    private const GEOCODING_API = 'https://geocoding-api.open-meteo.com/v1/search';
    private const WEATHER_API = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Get coordinates from postal code
     *
     * @param string $postalCode Postal code
     * @param string $country Country code (default: AT for Austria)
     * @return array{lat: float, lon: float, name: string}|null
     */
    public static function getCoordinatesFromPostalCode(string $postalCode, string $country = 'AT'): ?array
    {
        $cacheKey = 'weather_widget_coords_' . md5($postalCode . $country);

        // Check cache first
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        try {
            // Build geocoding request
            $url = add_query_arg([
                'name' => $postalCode,
                'count' => 1,
                'language' => 'de',
                'format' => 'json',
            ], self::GEOCODING_API);

            $response = wp_remote_get($url, [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if (is_wp_error($response)) {
                error_log('Weather Widget: Geocoding API error - ' . $response->get_error_message());
                return null;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data['results'][0])) {
                error_log('Weather Widget: No results found for postal code ' . $postalCode);
                return null;
            }

            $result = [
                'lat' => (float) $data['results'][0]['latitude'],
                'lon' => (float) $data['results'][0]['longitude'],
                'name' => $data['results'][0]['name'] ?? $postalCode,
            ];

            // Cache for 24 hours (postal codes don't change)
            set_transient($cacheKey, $result, DAY_IN_SECONDS);

            return $result;

        } catch (\Exception $e) {
            error_log('Weather Widget: Exception in geocoding - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get weather data from Open-Meteo API
     *
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param int $days Number of days (1-7)
     * @return array|null Weather data or null on error
     */
    public static function getWeatherData(float $latitude, float $longitude, int $days = 3): ?array
    {
        $days = max(1, min(7, $days)); // Ensure days is between 1 and 7

        $cacheKey = 'weather_widget_data_' . md5(sprintf('%f_%f_%d', $latitude, $longitude, $days));

        // Check cache first
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        try {
            // Build weather request with German timezone
            $url = add_query_arg([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'daily' => implode(',', [
                    'temperature_2m_max',
                    'temperature_2m_min',
                    'weathercode',
                    'precipitation_sum',
                    'windspeed_10m_max',
                ]),
                'current_weather' => 'true',
                'timezone' => 'Europe/Berlin',
                'forecast_days' => $days,
            ], self::WEATHER_API);

            $response = wp_remote_get($url, [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if (is_wp_error($response)) {
                error_log('Weather Widget: Weather API error - ' . $response->get_error_message());
                return null;
            }

            $statusCode = wp_remote_retrieve_response_code($response);
            if ($statusCode !== 200) {
                error_log('Weather Widget: Weather API returned status code ' . $statusCode);
                return null;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data['daily'])) {
                error_log('Weather Widget: Invalid weather data received');
                return null;
            }

            $processedData = self::processWeatherData($data);

            // Cache for 30 minutes
            set_transient($cacheKey, $processedData, self::CACHE_DURATION);

            return $processedData;

        } catch (\Exception $e) {
            error_log('Weather Widget: Exception in weather fetch - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process and format weather data
     *
     * @param array $rawData Raw API data
     * @return array Processed weather data
     */
    private static function processWeatherData(array $rawData): array
    {
        $processed = [
            'current' => [
                'temperature' => $rawData['current_weather']['temperature'] ?? 0,
                'weathercode' => $rawData['current_weather']['weathercode'] ?? 0,
                'windspeed' => $rawData['current_weather']['windspeed'] ?? 0,
                'time' => $rawData['current_weather']['time'] ?? '',
            ],
            'daily' => [],
        ];

        // Process daily data
        $count = count($rawData['daily']['time'] ?? []);
        for ($i = 0; $i < $count; $i++) {
            $processed['daily'][] = [
                'date' => $rawData['daily']['time'][$i] ?? '',
                'temp_max' => $rawData['daily']['temperature_2m_max'][$i] ?? 0,
                'temp_min' => $rawData['daily']['temperature_2m_min'][$i] ?? 0,
                'weathercode' => $rawData['daily']['weathercode'][$i] ?? 0,
                'precipitation' => $rawData['daily']['precipitation_sum'][$i] ?? 0,
                'windspeed' => $rawData['daily']['windspeed_10m_max'][$i] ?? 0,
            ];
        }

        return $processed;
    }

    /**
     * Get weather description from WMO weather code
     *
     * @param int $code WMO weather code
     * @return string Weather description in German
     */
    public static function getWeatherDescription(int $code): string
    {
        $descriptions = [
            0 => 'Klar',
            1 => 'Überwiegend klar',
            2 => 'Teilweise bewölkt',
            3 => 'Bewölkt',
            45 => 'Nebel',
            48 => 'Gefrierender Nebel',
            51 => 'Leichter Nieselregen',
            53 => 'Mäßiger Nieselregen',
            55 => 'Starker Nieselregen',
            61 => 'Leichter Regen',
            63 => 'Mäßiger Regen',
            65 => 'Starker Regen',
            71 => 'Leichter Schneefall',
            73 => 'Mäßiger Schneefall',
            75 => 'Starker Schneefall',
            77 => 'Schneekörner',
            80 => 'Leichte Regenschauer',
            81 => 'Mäßige Regenschauer',
            82 => 'Starke Regenschauer',
            85 => 'Leichte Schneeschauer',
            86 => 'Starke Schneeschauer',
            95 => 'Gewitter',
            96 => 'Gewitter mit leichtem Hagel',
            99 => 'Gewitter mit starkem Hagel',
        ];

        return $descriptions[$code] ?? 'Unbekannt';
    }

    /**
     * Get weather icon identifier from WMO code
     *
     * @param int $code WMO weather code
     * @return string Icon identifier
     */
    public static function getWeatherIcon(int $code): string
    {
        return match (true) {
            $code === 0 => 'clear',
            $code >= 1 && $code <= 3 => 'cloudy',
            $code >= 45 && $code <= 48 => 'fog',
            $code >= 51 && $code <= 57 => 'drizzle',
            $code >= 61 && $code <= 67 => 'rain',
            $code >= 71 && $code <= 77 => 'snow',
            $code >= 80 && $code <= 82 => 'showers',
            $code >= 85 && $code <= 86 => 'snow-showers',
            $code >= 95 && $code <= 99 => 'thunderstorm',
            default => 'unknown',
        };
    }

    /**
     * Clear all weather widget caches
     */
    public static function clearCache(): void
    {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_weather_widget_%'
             OR option_name LIKE '_transient_timeout_weather_widget_%'"
        );
    }
}
