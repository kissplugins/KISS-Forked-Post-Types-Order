# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.5.0] - 2025-01-04

### Fixed
- **CRITICAL PERFORMANCE**: Fixed N+1 query problem in category count calculation
- Replaced individual WP_Query calls for each taxonomy term with single optimized SQL query
- Added intelligent caching for term counts to prevent repeated expensive calculations
- Reduced database queries from N+1 to 1 for category dropdown generation

### Performance Improvements
- Category filter now loads significantly faster on sites with many taxonomy terms
- Memory usage reduced by eliminating multiple WP_Query instances
- Added 5-minute caching for term count calculations

### Technical Details
- Added `get_term_counts_optimized()` method for efficient batch term counting
- Implemented WordPress object caching with proper cache invalidation
- Optimized SQL query uses proper JOINs and GROUP BY for maximum efficiency

## [2.4.1] - 2025-01-04

### Changed
- Updated menu labels and page titles from "Re-Order" to "KISS Re-Order" for better branding consistency

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
