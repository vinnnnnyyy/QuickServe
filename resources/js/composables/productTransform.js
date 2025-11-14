// ============================================================================
// Constants
// ============================================================================
const PLACEHOLDER_IMAGE = 'https://via.placeholder.com/400x300?text=Menu+Item'
const DEFAULT_RATING = 4.6
const DEFAULT_REVIEW_COUNT = 128

// ============================================================================
// Helper Functions
// ============================================================================
/**
 * Convert a string to a URL-friendly slug
 */
export const slugify = (str) => {
  if (!str) return ''
  return str
    .toString()
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
}

// ============================================================================
// Product Transformation
// ============================================================================
/**
 * Transform API menu item to UI product format
 * 
 * @param {Object} item - Raw menu item from API
 * @param {Object} config - Optional configuration overrides
 * @returns {Object} Transformed product object for UI consumption
 */
export const toProduct = (item, config = {}) => {
  const {
    placeholderImage = PLACEHOLDER_IMAGE,
    defaultRating = DEFAULT_RATING,
    defaultReviewCount = DEFAULT_REVIEW_COUNT
  } = config

  // Extract category name from relation or fallback to string
  const categoryName = item?.category?.name ?? item?.category ?? 'uncategorized'
  
  return {
    id: item?.id,
    name: item?.name,
    description: item?.description ?? '',
    // Use price_formatted accessor (cents to dollars) or fallback
    price: Number(item?.price_formatted ?? ((item?.price ?? 0) / 100)),
    // Use image_url accessor from model for storage symlink
    image: item?.image_url ?? placeholderImage,
    category: slugify(categoryName),
    // Use deterministic defaults instead of random values
    rating: item?.rating ?? defaultRating,
    reviewCount: item?.review_count ?? defaultReviewCount,
    badge: item?.featured ? {
      text: 'Featured',
      color: 'bg-blue-500 text-white'
    } : item?.popular ? {
      text: 'Popular',
      color: 'bg-primary-500 text-white'
    } : null,
    status: {
      text: item?.available ? 'Available' : 'Out of Stock',
      color: item?.available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
    },
    tags: [
      categoryName,
      item?.temperature,
      ...(item?.popular ? ['popular'] : [])
    ].filter(Boolean)
  }
}

/**
 * Format price as currency
 * 
 * @param {number} price - Price value
 * @returns {string} Formatted price string
 */
export const formatPrice = (price) => `₱${Number(price).toFixed(2)}`

// Export constants for external use if needed
export { PLACEHOLDER_IMAGE, DEFAULT_RATING, DEFAULT_REVIEW_COUNT }
