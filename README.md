# Reading Progress

A dependency-free WordPress plugin: a slim reading-progress bar (top or bottom of the viewport) plus an estimated reading time for posts.

## Features

- Fixed progress bar — colour, height (2–12px) and position configurable, no JS libraries
- Reading time prepended to single posts, based on word count (default 200 wpm, filterable via `wrp_words_per_min`)
- `[reading_time]` shortcode for manual placement ("5 min read")
- Post-type aware — defaults to posts only
- Nothing is written into posts, so deactivating leaves content untouched

## Settings

**Settings → Reading Progress**: bar colour/height/position, reading-time toggle, words-per-minute.

## Install

Upload the plugin folder to `wp-content/plugins/` and activate it.

## Uninstall

Removes the single `wrp_settings` option.

## License

MIT
