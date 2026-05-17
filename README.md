# Audialis — Audio Player & Manager

A lightweight self-hosted audio player with Web Audio API sound processing, noise reduction, and web-based file management.

## Features

- **Dual view player** — Grid view (track cards with indices) and Playlist view (full list with auto-advance)
- **Sound controller** — Volume control and noise reduction (high-pass / low-pass filters) via Web Audio API
- **Shuffle & search** — Random playback and real-time track filtering
- **Admin panel** — Upload (drag & drop + multi-select), rename, and delete audio files
- **Session auth** — Password-protected admin with configurable timeout
- **Auto-sorted** — All tracks listed alphabetically

## Requirements

- PHP 7.4+
- A web server (built-in PHP server works)

## Quick Start

```bash
# Start the PHP development server
php -S localhost:8899 -t /path/to/audialis

# Open in browser
# Player:  http://localhost:8899/index.html
# Admin:   http://localhost:8899/admin.php     (configurable timeout)
# Admin:   http://localhost:8899/adminv2.php   (fixed 30-day timeout with countdown)
```

Add `.mp3`, `.wav`, `.ogg`, `.flac`, `.aac`, `.m4a`, or `.wma` files to the `audio/` folder.

## Default Login

- **Password:** `admin`

## Project Structure

```
├── index.html       # Audio player (grid/playlist views)
├── admin.php        # Admin panel with configurable session timeout
├── adminv2.php      # Admin panel with fixed 30-day timeout + live countdown
├── list.php         # JSON endpoint listing audio files
├── audio/           # Directory for audio files
└── README.md
```
