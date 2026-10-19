import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './app/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}'
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
          950: '#052e16'
        },
        accent: {
          50: '#ecfeff',
          100: '#cffafe',
          200: '#a5f3fc',
          300: '#67e8f9',
          400: '#22d3ee',
          500: '#06b6d4',
          600: '#0891b2',
          700: '#0e7490'
        }
      },
      fontFamily: {
        sans: [
          'Inter',
          'ui-sans-serif',
          'system-ui',
          '-apple-system',
          'Segoe UI',
          'Roboto',
          'sans-serif'
        ]
      },
      borderRadius: {
        '4xl': '2rem',
        '5xl': '2.5rem'
      },
      boxShadow: {
        soft: '0 1px 2px rgba(16,24,40,0.04), 0 12px 32px -14px rgba(16,24,40,0.18)',
        card: '0 1px 2px rgba(16,24,40,0.04), 0 24px 48px -24px rgba(16,24,40,0.22)',
        glow: '0 12px 30px -10px rgba(22,163,74,0.45)',
        'glow-lg': '0 22px 46px -14px rgba(22,163,74,0.55)',
        inset: 'inset 0 1px 2px rgba(16,24,40,0.06)',
        field: '0 1px 2px rgba(16,24,40,0.05)',
        'field-focus': '0 0 0 4px rgba(34,197,94,0.14), 0 6px 18px -8px rgba(22,163,74,0.35)'
      },
      backgroundImage: {
        'brand-gradient': 'linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #15803d 100%)',
        'brand-soft': 'linear-gradient(135deg, #f0fdf4 0%, #ffffff 55%, #dcfce7 100%)',
        'accent-gradient': 'linear-gradient(135deg, #22d3ee 0%, #0891b2 100%)',
        'grid-slate':
          'linear-gradient(to right, rgba(15,23,42,.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(15,23,42,.05) 1px, transparent 1px)',
        shine: 'linear-gradient(110deg, transparent 20%, rgba(255,255,255,.6) 45%, transparent 70%)'
      },
      backgroundSize: {
        grid: '26px 26px'
      },
      transitionTimingFunction: {
        spring: 'cubic-bezier(.22,1,.36,1)'
      },
      animation: {
        'fade-in': 'fadeIn .5s ease-out forwards',
        'slide-up': 'slideUp .55s cubic-bezier(.22,1,.36,1) both',
        'slide-down': 'slideDown .5s cubic-bezier(.22,1,.36,1) both',
        'scale-in': 'scaleIn .4s cubic-bezier(.22,1,.36,1) both',
        float: 'float 7s ease-in-out infinite',
        'pulse-slow': 'pulse 4.5s cubic-bezier(.4,0,.6,1) infinite',
        'spin-slow': 'spin 14s linear infinite',
        shake: 'shake .45s cubic-bezier(.36,.07,.19,.97) both',
        shimmer: 'shimmer 2.4s linear infinite',
        'gradient-x': 'gradientX 12s ease infinite',
        pop: 'pop .4s cubic-bezier(.22,1,.36,1) both'
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(18px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        slideDown: {
          '0%': { opacity: '0', transform: 'translateY(-14px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        shake: {
          '10%,90%': { transform: 'translateX(-1px)' },
          '20%,80%': { transform: 'translateX(2px)' },
          '30%,50%,70%': { transform: 'translateX(-4px)' },
          '40%,60%': { transform: 'translateX(4px)' }
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(.94)' },
          '100%': { opacity: '1', transform: 'scale(1)' }
        },
        float: {
          '0%,100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-14px)' }
        },
        shimmer: {
          '0%': { backgroundPosition: '200% 0' },
          '100%': { backgroundPosition: '-200% 0' }
        },
        gradientX: {
          '0%,100%': { backgroundPosition: '0% 50%' },
          '50%': { backgroundPosition: '100% 50%' }
        },
        pop: {
          '0%': { opacity: '0', transform: 'scale(.8)' },
          '60%': { transform: 'scale(1.04)' },
          '100%': { opacity: '1', transform: 'scale(1)' }
        }
      }
    }
  },
  plugins: []
};

export default config;
