import { ref, computed } from 'vue';

// Centralized menu categories configuration
export const useMenuCategories = () => {
  // Base category options with consistent structure
  const baseCategoryOptions = ref([
    { 
      value: 'hot_drinks', 
      label: 'Hot Drinks',
      icon: 'local_fire_department',
      description: 'Coffee, tea, and other hot beverages'
    },
    { 
      value: 'cold_drinks', 
      label: 'Cold Drinks',
      icon: 'ac_unit',
      description: 'Iced coffee, cold brew, and chilled beverages'
    },
    { 
      value: 'specialty_coffee', 
      label: 'Specialty Coffee',
      icon: 'coffee_maker',
      description: 'Premium and artisan coffee selections'
    },
    { 
      value: 'tea', 
      label: 'Tea & Infusions',
      icon: 'emoji_food_beverage',
      description: 'Traditional teas and herbal infusions'
    },
    { 
      value: 'pastries', 
      label: 'Pastries',
      icon: 'cake',
      description: 'Fresh baked goods and sweet treats'
    },
    { 
      value: 'sandwiches', 
      label: 'Sandwiches',
      icon: 'lunch_dining',
      description: 'Fresh sandwiches and wraps'
    },
    { 
      value: 'desserts', 
      label: 'Desserts',
      icon: 'icecream',
      description: 'Sweet desserts and confections'
    },
    { 
      value: 'salads', 
      label: 'Salads',
      icon: 'grass',
      description: 'Fresh and healthy salad options'
    },
    { 
      value: 'breakfast', 
      label: 'Breakfast',
      icon: 'free_breakfast',
      description: 'Morning meals and breakfast items'
    },
    { 
      value: 'snacks', 
      label: 'Snacks',
      icon: 'cookie',
      description: 'Light snacks and quick bites'
    }
  ]);

  // Additional custom categories (can be added dynamically)
  const customCategories = ref([]);

  // Combined categories
  const allCategories = computed(() => [
    ...baseCategoryOptions.value,
    ...customCategories.value
  ]);

  // Get category by value
  const getCategoryByValue = (value) => {
    return allCategories.value.find(cat => cat.value === value);
  };

  // Get category by label
  const getCategoryByLabel = (label) => {
    return allCategories.value.find(cat => cat.label === label);
  };

  // Get category icon
  const getCategoryIcon = (categoryValue) => {
    const category = getCategoryByValue(categoryValue);
    return category?.icon || 'restaurant_menu';
  };

  // Get category label
  const getCategoryLabel = (categoryValue) => {
    const category = getCategoryByValue(categoryValue);
    return category?.label || categoryValue;
  };

  // Add new category
  const addCategory = (categoryData) => {
    const newCategory = {
      value: categoryData.value || categoryData.label.toLowerCase().replace(/\s+/g, '_'),
      label: categoryData.label,
      icon: categoryData.icon || 'restaurant_menu',
      description: categoryData.description || '',
      custom: true
    };

    // Check if category already exists
    const exists = allCategories.value.find(cat => 
      cat.value === newCategory.value || cat.label === newCategory.label
    );

    if (!exists) {
      customCategories.value.push(newCategory);
      return newCategory;
    }

    return null; // Category already exists
  };

  // Remove custom category
  const removeCategory = (categoryValue) => {
    const index = customCategories.value.findIndex(cat => cat.value === categoryValue);
    if (index !== -1) {
      customCategories.value.splice(index, 1);
      return true;
    }
    return false;
  };

  // Format categories for FormSelect component
  const getFormSelectOptions = () => {
    return allCategories.value.map(cat => ({
      value: cat.value,
      label: cat.label
    }));
  };

  // Get categories with counts for filtering (requires menu items)
  const getCategoriesWithCounts = (menuItems = []) => {
    const categoryMap = new Map();
    
    // Initialize all categories with 0 count
    allCategories.value.forEach(category => {
      categoryMap.set(category.value, {
        ...category,
        count: 0
      });
    });

    // Count items in each category
    menuItems.forEach(item => {
      const categoryValue = item.category ? 
        getCategoryByLabel(item.category)?.value || 'uncategorized' : 
        'uncategorized';
      
      if (categoryMap.has(categoryValue)) {
        const category = categoryMap.get(categoryValue);
        category.count++;
      } else {
        // Handle uncategorized items
        categoryMap.set('uncategorized', {
          value: 'uncategorized',
          label: 'Uncategorized',
          icon: 'help',
          count: (categoryMap.get('uncategorized')?.count || 0) + 1
        });
      }
    });

    return Array.from(categoryMap.values()).filter(category => category.count > 0);
  };

  return {
    baseCategoryOptions,
    customCategories,
    allCategories,
    getCategoryByValue,
    getCategoryByLabel,
    getCategoryIcon,
    getCategoryLabel,
    addCategory,
    removeCategory,
    getFormSelectOptions,
    getCategoriesWithCounts
  };
};
