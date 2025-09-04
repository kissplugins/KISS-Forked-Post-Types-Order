# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.7.0] - 2025-01-04

### Security
- **CRITICAL SECURITY**: Implemented comprehensive AJAX input validation and sanitization
- Added authentication checks for all AJAX handlers
- Enhanced nonce validation with proper error handling
- Added capability checks for post type access
- Implemented post ownership validation before allowing edits
- Added input format validation with regex patterns
- Enhanced error messages with proper internationalization

### Security Improvements
- **Authentication**: All AJAX handlers now verify user login status
- **Authorization**: Capability checks for `edit_posts` and post-type-specific permissions
- **Input Validation**: Comprehensive sanitization of all user inputs
- **Nonce Security**: Strengthened nonce validation with detailed error responses
- **Data Validation**: Post ID validation and ownership verification
- **Format Validation**: Regex validation for taxonomy filters and post type names
- **Error Handling**: Secure error responses using `wp_send_json_error()`

### Technical Security Details
- Enhanced `saveAjaxOrder()` with multi-layer validation
- Improved `filterPostsByCategory()` with taxonomy/term existence checks
- Strengthened `saveArchiveAjaxOrder()` with data format validation
- Added `processOrderData()` method with post ownership verification
- Enhanced `admin_init()` with post type access control

### Vulnerability Fixes
- Fixed potential privilege escalation in post reordering
- Prevented unauthorized access to post type interfaces
- Blocked invalid taxonomy/term manipulation
- Secured against malformed input data attacks
- Protected against CSRF attacks with enhanced nonce validation

## [2.6.0] - 2025-01-04

### Added
- **CRITICAL PERFORMANCE**: Implemented pagination for main post interface
- Added comprehensive pagination controls with page navigation, jump-to-page functionality
- Pagination now limits posts to 50 per page instead of loading all posts
- Added pagination support to AJAX category filtering

### Fixed
- **CRITICAL PERFORMANCE**: Eliminated unbounded queries in main interface (`posts_per_page => -1`)
- Fixed AJAX filter queries to use pagination instead of loading all posts
- Improved memory usage by limiting post loading to manageable chunks

### Performance Improvements
- Main interface now loads 50 posts per page instead of all posts (unlimited)
- Significantly reduced memory usage on sites with thousands of posts
- Faster page load times and improved responsiveness
- AJAX filtering now respects pagination limits

### User Experience
- Added intuitive pagination controls with previous/next navigation
- Implemented page number links with smart ellipsis for large page counts
- Added "jump to page" functionality for quick navigation
- Pagination state preserved during category filtering

### Technical Details
- Added `render_pagination_controls()` method for consistent pagination UI
- Enhanced AJAX responses to include pagination metadata
- Implemented proper URL parameter handling for pagination state
- Added pagination info storage in interface class

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
