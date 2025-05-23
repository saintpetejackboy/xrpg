# CSS Summary

## Files

* **variables.css**: core CSS vars (colors, gradients, shadows, opacities, fonts, timing, z-indices, spacing, radius, blur)
* **components.css**: UI components (buttons, inputs, cards, modals, nav, header, footer, content)
* **overrides.css**: browser-specific fixes (scrollbars, focus outlines)
* **theme.css**: imports + dynamic user vars & theming logic (light/dark, user controls)
* **README.md**: documentation & guidelines

## Variables

* **Colors**: `--color-*` (bg, surface, accent, text, semantic)
* **Gradients**: `--gradient-*`
* **Shadows**: `--shadow-*`
* **Opacities**: `--opacity-*`
* **Fonts**: `--font-*`
* **Timing**: `--duration-*`, `--ease-*`
* **Z-index**: `--z-*`
* **Spacing**: `--space-*`
* **Radius**: `--radius-*`
* **Blur**: `--blur-*`

## Components

* **Buttons**: `.button` (gradient bg, hover/active, variants, sizes, icon)
* **Inputs**: `input, textarea, select`; special types (checkbox, radio, range, color)
* **Cards & Surfaces**: `.card`, `.surface` (hover effects)
* **Modals**: `.modal`, `dialog` (open/close animations, backdrop)
* **Navigation**: `.side-nav`, `.main-header`, `.main-footer`
* **Layout**: `.main-content` (nav offset)

## Utilities

* **Display**: `.hidden`, `.invisible`, `.sr-only`
* **Text**: `.text-*`
* **BG**: `.bg-*`
* **Spacing**: `.m-*`, `.p-*`
* **Flex/Grid**: `.flex`, `.justify-*`, `.grid-cols-*`
* **Animation**: `.animate-*`, keyframes (pulse, bounce, spin)

## Special Components

* **Progress**: `.progress`, `.progress-fill` (shimmer)
* **Badges**: `.badge`, semantic variants
* **Tooltips**: `[data-tooltip]`
* **Spinner**: `.spinner`
* **FAB & Theme Toggle**: `.fab`, `.theme-toggle-fixed`
* **Updates Area**: `.updates-area`, `.update-entry`
* **Forms**: `.form-group`, `.control-group`
* **Contrast Warning**: `.contrast-warning`
* **Theme Preview**: `.theme-preview-circle`

## Theming & Accessibility

* **Dynamic Controls**: `--user-*` vars in `theme.css`
* **Light/Dark**: `[data-theme="light"]` overrides
* **Focus**: `:focus-visible`
* **High Contrast**: `prefers-contrast`
* **Reduced Motion**: `prefers-reduced-motion`

## Guidelines

* Use CSS variables for all styles
* Define hover/active/focus/disabled states
* GPU-accelerated transitions
* Test with extreme user settings
