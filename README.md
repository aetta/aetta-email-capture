# Quick Email Capture

Simple, fast and lightweight email capture for WordPress. No bloat.

## Features

- Shortcode: `[quick_email_capture]`
- Consent checkbox (GDPR-friendly)
- Honeypot + basic anti-spam
- Email deduplication
- CSV export (Excel-safe)
- Optional IP and user-agent storage
- Automatic retention and purge
- Translation-ready

## Installation (local/dev)

1. Copy `quick-email-capture/` into `wp-content/plugins/`
2. Activate **Quick Email Capture**
3. Add `[quick_email_capture]` to any page/post

## Settings

Go to: **WP Admin → Quick Email Capture → Settings**

- Behavior: retention, rate limit, anti-bot minimum time
- Privacy: optionally store IP and user agent
- Styling: built-in CSS toggle, extra classes, theme controls

## Data & Privacy

Signups are stored as private entries in WordPress.
You can export CSV from the admin.
The plugin adds Privacy Policy content and supports the WP personal data exporter/eraser.

## Development

- PHP: 7.4+
- WordPress: 6.0+
- Text domain: `quick-email-capture`

## License

GPLv2 or later.
