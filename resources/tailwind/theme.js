// resources/tailwind/theme.js
export const themeExtend = {
    screens: {
      xs: '320px',
      sm: '640px',
      md: '768px',
      lg: '1024px',
      xl: '1280px',
      '2xl': '1536px',
    },
    colors: {
      primary: {
        DEFAULT: '#ec7813',
        50: '#fff7ed',
        100: '#ffedd5',
        200: '#fed7aa',
        300: '#fdba74',
        400: '#fb923c',
        500: '#f97316',
        600: '#ea580c',
        700: '#c2410c',
        800: '#9a3412',
        900: '#7c2d12',
      },
      'background-light': '#f8f7f6',
      'background-dark': '#221810',
      surface: {
        50: '#fafafa',
        100: '#f4f4f5',
        200: '#e4e4e7',
        300: '#d4d4d8',
        400: '#a1a1aa',
        500: '#71717a',
        600: '#52525b',
        700: '#3f3f46',
        800: '#27272a',
        900: '#18181b',
      }
    },
    animation: {
      'fade-in': 'fade-in 0.5s ease-out',
      'slide-up': 'slide-up 0.5s ease-out',
      'slide-down': 'slide-down 0.3s ease-out',
      'slide-in-left': 'slide-in-left 0.3s ease-out',
      'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
    },
    keyframes: {
      'fade-in': {
        '0%': { opacity: '0' },
        '100%': { opacity: '1' },
      },
      'slide-up': {
        '0%': { transform: 'translateY(10px)', opacity: '0' },
        '100%': { transform: 'translateY(0)', opacity: '1' },
      },
      'slide-down': {
        '0%': { transform: 'translateY(-10px)', opacity: '0' },
        '100%': { transform: 'translateY(0)', opacity: '1' },
      },
      'slide-in-left': {
        '0%': { transform: 'translateX(-10px)', opacity: '0' },
        '100%': { transform: 'translateX(0)', opacity: '1' },
      },
    },
    backdropBlur: {
      xs: '2px',
    }
  };