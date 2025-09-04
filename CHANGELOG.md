# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.0] - 2025-01-04

### Removed
- Plugin Information & Support info box from the Post Types Order interface for cleaner UI

### Fixed
- Dropdown category count accuracy - counts now reflect posts specific to the current post type instead of all post types
- Category filter debugging information now shows accurate post counts per taxonomy term

### Technical Details
- Replaced `$term->count` with custom WP_Query to calculate accurate post counts per taxonomy term
- Updated both dropdown generation and debug display logic to use post-type-specific queries
- Improved code maintainability by using consistent counting methodology

## Previous Versions

For changelog entries prior to version 2.4.0, please refer to the `readme-original.txt` file which contains the complete version history from the original plugin.
