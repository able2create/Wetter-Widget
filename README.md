# WordPress Wetter Widget

Ein minimalistisches, performantes Wetter-Widget für WordPress mit Open-Meteo API Integration.

## Features

- ✨ **Moderne PHP 8+ Syntax** mit Type Declarations und Match Expressions
- 🎨 **Minimalistisches Design** mit CSS Grid und Custom Properties
- 🌓 **Dark Mode Support** (automatisch basierend auf Systemeinstellungen)
- ⚡ **Performance-optimiert** mit Transients Caching (30 Min.)
- 📱 **Responsive Design** (Mobile-first Approach)
- ♿ **Accessibility** (WCAG 2.1 kompatibel)
- 🎯 **Conditional Loading** (Assets nur bei Bedarf laden)
- 🔌 **Einfache Integration** (Shortcode, Template Tag, Widget)
- 🌍 **Deutsche Lokalisierung**
- 🔒 **WordPress Coding Standards**

## Verzeichnisstruktur

```
inc/
├── weather-widget/
│   ├── weather-widget.php      # Hauptdatei
│   ├── weather-api.php          # API-Integration
│   ├── weather-admin.php        # Backend-Einstellungen
│   ├── weather-frontend.php     # Frontend-Ausgabe
│   └── weather-widget.css       # Styling
└── weather-widget-loader.php    # Theme-Loader
```

## Installation

### 1. Dateien kopieren

Kopieren Sie die Verzeichnisse `inc/weather-widget/` und die Datei `inc/weather-widget-loader.php` in Ihr Theme-Verzeichnis:

```
your-theme/
├── inc/
│   ├── weather-widget/
│   └── weather-widget-loader.php
└── functions.php
```

### 2. Widget aktivieren

Fügen Sie folgende Zeile in Ihre `functions.php` ein:

```php
require_once get_template_directory() . '/inc/weather-widget-loader.php';
```

### 3. Einstellungen konfigurieren

Gehen Sie zu **Einstellungen > Wetter Widget** im WordPress Admin und konfigurieren Sie:

- **Postleitzahl**: Deutsche PLZ (5 Ziffern)
- **Anzahl Tage**: 1-7 Tage Vorhersage
- **Icons anzeigen**: Wetter-Icons aktivieren/deaktivieren

## Verwendung

### Shortcode (in Posts/Pages)

```
[weather_widget]
```

Mit Parametern:

```
[weather_widget postal_code="10115" days="5" show_icons="1"]
```

### Template Tag (in PHP-Dateien)

```php
<?php \WeatherWidget\display_weather_widget(); ?>
```

Mit Parametern:

```php
<?php
\WeatherWidget\display_weather_widget([
    'postal_code' => '10115',
    'days' => 5,
    'show_icons' => true
]);
?>
```

### WordPress Widget (Sidebar)

1. Gehen Sie zu **Design > Widgets**
2. Fügen Sie das **Wetter Widget** zu Ihrer Sidebar hinzu
3. Konfigurieren Sie optional einen Titel

## API-Integration

Das Widget verwendet die kostenlose [Open-Meteo API](https://open-meteo.com/):

- **Geocoding API**: Konvertiert PLZ zu Koordinaten
- **Weather Forecast API**: Liefert Wetterdaten für 7 Tage

**Keine API-Keys erforderlich!** Die Open-Meteo API ist kostenlos und erfordert keine Registrierung.

## Performance-Optimierungen

### Caching

- **Wetterdaten**: 30 Minuten Cache (WordPress Transients)
- **Koordinaten**: 24 Stunden Cache (PLZ ändern sich nicht)

Cache manuell leeren:

```php
\WeatherWidget\WeatherAPI::clearCache();
```

Oder über die Einstellungsseite: **Einstellungen > Wetter Widget > Cache jetzt leeren**

### Asset Loading

- **Conditional Loading**: CSS wird nur geladen, wenn Shortcode vorhanden ist
- **Inline CSS**: Kritisches CSS inline für bessere Performance
- **SVG Icons**: Inline SVGs statt Icon-Fonts

## Anpassungen

### CSS Variablen

Passen Sie das Design über CSS Custom Properties an:

```css
:root {
    --weather-widget-bg: #ffffff;
    --weather-widget-text: #1a1a1a;
    --weather-widget-accent: #2563eb;
    --weather-widget-radius: 12px;
    --weather-widget-spacing: 1rem;
}
```

### Filter Hooks

```php
// Assets global laden (statt nur bei Shortcode)
add_filter('weather_widget_load_assets', '__return_true');
```

## Wetter-Codes (WMO)

Das Widget verwendet die WMO Weather Interpretation Codes:

| Code | Beschreibung |
|------|--------------|
| 0 | Klar |
| 1-3 | Bewölkt (leicht bis stark) |
| 45-48 | Nebel |
| 51-57 | Nieselregen |
| 61-67 | Regen |
| 71-77 | Schneefall |
| 80-82 | Regenschauer |
| 85-86 | Schneeschauer |
| 95-99 | Gewitter |

## Browser-Unterstützung

- Chrome/Edge 88+
- Firefox 78+
- Safari 14+
- Opera 74+

## Systemanforderungen

- PHP 8.0+
- WordPress 6.0+
- Modern Browser mit CSS Grid Support

## Troubleshooting

### Keine Wetterdaten angezeigt

1. Überprüfen Sie die Postleitzahl in den Einstellungen
2. Leeren Sie den Cache: **Einstellungen > Wetter Widget > Cache leeren**
3. Prüfen Sie die Fehlerlogs: `wp-content/debug.log`

### Widget lädt nicht

Stellen Sie sicher, dass:
- Alle Dateien korrekt kopiert wurden
- Die Loader-Datei in `functions.php` eingebunden ist
- PHP 8.0+ installiert ist

### Styling-Probleme

- Prüfen Sie Theme-CSS auf Konflikte
- Verwenden Sie spezifischere Selektoren
- Deaktivieren Sie andere Wetter-Plugins

## Entwicklung

### Code-Standards

- PHP 8.0+ Syntax (Typed Properties, Named Arguments, etc.)
- WordPress Coding Standards
- PSR-12 Coding Style Guide

### Testing

```bash
# PHP Syntax Check
php -l inc/weather-widget/*.php

# WordPress Coding Standards (PHPCS)
phpcs --standard=WordPress inc/weather-widget/
```

## Lizenz

Dieses Projekt steht unter der MIT-Lizenz.

## Credits

- **Wetterdaten**: [Open-Meteo API](https://open-meteo.com/)
- **Icons**: [Feather Icons](https://feathericons.com/) (SVG)

## Support

Bei Fragen oder Problemen:

1. Prüfen Sie die Dokumentation
2. Durchsuchen Sie die WordPress Debug-Logs
3. Erstellen Sie ein Issue im Repository

## Changelog

### Version 1.0.0 (2024)

- Initial Release
- Open-Meteo API Integration
- Responsive Design
- Dark Mode Support
- Performance-Optimierungen
- WordPress Settings API
- Shortcode & Template Tag
- Widget-Support

## Roadmap

- [ ] Gutenberg Block
- [ ] Elementor Widget
- [ ] Mehrsprachigkeit (i18n)
- [ ] Zusätzliche Wetter-Provider
- [ ] Erweiterte Statistiken
- [ ] Export-Funktion

---

**Entwickelt mit ❤️ für WordPress**
