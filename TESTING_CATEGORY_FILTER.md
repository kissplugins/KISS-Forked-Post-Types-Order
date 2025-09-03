# Testing Category Filter Feature

## Overview
This document outlines how to test the new category filtering feature added in version 2.3.8 of the Post Types Order plugin.

## Prerequisites
1. WordPress installation with the Post Types Order plugin activated
2. A post type with posts assigned to different categories/taxonomies
3. At least 5-10 posts with various category assignments for proper testing

## Test Cases

### Test Case 1: Basic Category Filter Display
**Objective**: Verify that the category filter dropdown appears and is populated correctly.

**Steps**:
1. Navigate to the Post Types Order interface for any non-hierarchical post type
2. Look for the "Filter by Category:" dropdown above the sortable list
3. Verify the dropdown contains:
   - "All Categories" as the first option
   - Grouped options by taxonomy (e.g., Categories, Tags)
   - Each category shows the post count in parentheses

**Expected Result**: Category filter dropdown is visible and properly populated.

### Test Case 2: Category Filtering Functionality
**Objective**: Test that filtering by category works correctly.

**Steps**:
1. Select a category from the dropdown
2. Click the "Filter" button
3. Verify that only posts from the selected category are displayed
4. Check that the "Clear Filter" button appears
5. Verify the success message shows the correct number of filtered posts

**Expected Result**: Only posts from the selected category are shown in the sortable list.

### Test Case 3: Post Reordering Within Category Filter
**Objective**: Test that posts can be reordered when a category filter is active.

**Steps**:
1. Apply a category filter that shows multiple posts
2. Drag and drop posts to reorder them within the filtered view
3. Click "Update" to save the new order
4. Verify the success message appears
5. Refresh the page and reapply the same filter
6. Confirm the new order is preserved

**Expected Result**: Posts maintain their new order within the filtered category.

### Test Case 4: Global Order Preservation
**Objective**: Verify that reordering within a category filter doesn't break the global order.

**Steps**:
1. Note the initial global order (view "All Categories")
2. Apply a category filter and reorder some posts
3. Save the changes
4. Clear the filter to view all posts again
5. Verify that:
   - Posts that were reordered maintain their new relative positions
   - Posts that weren't in the filter maintain their original relative positions
   - The overall sequence makes sense

**Expected Result**: Global order is preserved while respecting the new order of filtered posts.

### Test Case 5: Clear Filter Functionality
**Objective**: Test the clear filter feature.

**Steps**:
1. Apply any category filter
2. Click the "Clear Filter" button
3. Verify that:
   - The dropdown resets to "All Categories"
   - All posts are displayed again
   - The "Clear Filter" button disappears
   - The "Filter" button text returns to "Filter"

**Expected Result**: Filter is cleared and all posts are displayed.

### Test Case 6: AJAX Error Handling
**Objective**: Test error handling for AJAX requests.

**Steps**:
1. Temporarily disable JavaScript or simulate network issues
2. Try to apply a category filter
3. Verify appropriate error messages are displayed

**Expected Result**: Graceful error handling with user-friendly messages.

## Browser Compatibility Testing
Test the functionality in:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Performance Testing
- Test with a large number of posts (100+)
- Verify filtering and reordering performance remains acceptable
- Check for memory leaks during repeated filtering operations

## Notes for Developers
- The category filter uses AJAX action `pto_filter_posts_by_category`
- Order preservation logic is handled in `saveFilteredAjaxOrder()` method
- JavaScript functionality is embedded in the interface page
- Nonce verification is used for security

## Known Limitations
- Only works with non-hierarchical post types (as per plugin design)
- Filtering is limited to hierarchical taxonomies (categories, not tags)
- Requires JavaScript to be enabled

## Reporting Issues
If any test case fails, please document:
1. WordPress version
2. PHP version
3. Browser and version
4. Exact steps to reproduce
5. Expected vs actual behavior
6. Console errors (if any)
