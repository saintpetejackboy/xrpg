// \\wsl.localhost\Ubuntu\var\www\xrpg\assets\css\theme\README.md
# Theme System Guide

## Variables
All appearance-related colors, shadows, gradients, and opacities are declared in `variables.css`.  
EVERY UI element, component, or aria-area should reference these variables—never hardcode colors or shadows.

## Components
`components.css` contains all standard classes for UI elements (buttons, cards, inputs, etc), using variables for all appearance.

## Overrides
`overrides.css` covers scrollbars, borders, accessibility styles (e.g. focus/focus-visible) to ensure full theme coverage across browsers and future elements.

## Extending
- For new UI, ALWAYS use CSS variables for appearance: background, color, border, shadow, gradients, opacity.
- If adding new variables, document them here and use naming conventions following `--color-x`, `--gradient-x`, `--shadow-x`, etc.

## Accessibility
Keep contrast high (minimum AA), and design for all users.
