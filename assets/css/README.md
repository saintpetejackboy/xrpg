# XRPG Theme System Documentation

## Overview
The XRPG theme system provides complete visual customization through CSS variables, allowing players to personalize their experience with dynamic colors, opacity, shadows, and border radius adjustments.

## File Structure
```
/assets/css/
├── theme.css       # Main theme file (imports all others)
├── variables.css   # Core CSS variables & theme presets
├── components.css  # UI component styles
├── overrides.css   # Browser-specific overrides
└── README.md       # This documentation
```

## Core Variables

### Colors
- `--color-bg`: Main background color
- `--color-surface`: Card/panel backgrounds
- `--color-surface-alt`: Alternative surface (inputs, etc)
- `--color-accent`: Primary accent color (user-controlled)
- `--color-accent-glow`: Accent color for glows/shadows
- `--color-border`: Border colors
- `--color-text`: Primary text color
- `--color-muted`: Secondary/muted text

### Gradients
- `--gradient-accent`: Accent gradient (buttons, highlights)
- `--gradient-surface`: Surface gradient (cards, panels)

### Shadows
- `--shadow-default`: Standard shadow with accent glow
- `--shadow-glow`: Intense glow effect for focus/active states

### Opacities
- `--opacity-ui`: UI element opacity (user-controlled)
- `--opacity-modal`: Modal/dialog opacity
- `--opacity-active`: Active state opacity
- `--opacity-disabled`: Disabled element opacity

### User Controls
Players can adjust these via the UI:
- `--user-accent`: Primary accent color
- `--user-accent2`: Secondary accent for gradients
- `--user-radius`: Border radius (9-40px)
- `--user-shadow-intensity`: Shadow darkness (0.05-0.35)
- `--user-opacity`: UI opacity (0.8-1.0)

## Component Classes

### Buttons
```css
.button, button, [role="button"]
```
- Uses accent gradient background
- Glowing shadow on hover/active
- Respects opacity settings

### Inputs
```css
input, textarea, select
```
- Dark surface background
- Accent border on focus
- Glow effect when active

### Cards & Surfaces
```css
.card, .modal, .surface
```
- Gradient surface background
- Subtle border
- Respects border radius settings

### Utility Classes
- `.bg-accent`: Accent gradient background
- `.bg-surface`: Surface gradient background
- `.text-accent`: Accent colored text
- `.text-muted`: Muted colored text

## Accessibility Features

### ARIA Support
All elements with ARIA attributes automatically inherit theme colors:
```css
[role="region"], [aria-label], .accessible-panel
```

### Focus Indicators
Strong focus indicators using accent color:
```css
:focus-visible {
  outline: 2.5px solid var(--color-accent);
  outline-offset: 2px;
}
```

### Scrollbar Theming
Custom scrollbars that match the theme:
- Webkit browsers: Full visual customization
- Firefox: Basic color theming
- Scrollbar colors blend with accent color

## Usage Guidelines

### Adding New Components
1. **Always use CSS variables** - Never hardcode colors
2. **Include all states** - hover, active, focus, disabled
3. **Test with all theme variations** - Light/dark, different accents
4. **Ensure accessibility** - Minimum AA contrast ratios

### Example Component
```css
.new-component {
  background: var(--gradient-surface);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  border-radius: calc(var(--user-radius) * 0.5);
  box-shadow: var(--shadow-default);
  opacity: var(--opacity-ui);
}

.new-component:hover {
  box-shadow: var(--shadow-glow);
}
```

## Theme Switching
The system supports multiple theme presets:
- **Dark** (default): Dark backgrounds with vibrant accents
- **Light**: Bright backgrounds with deeper accents
- **Custom**: User-defined colors and settings

## Performance Notes
- All transitions are GPU-accelerated
- Color calculations use native CSS functions
- Variables cascade efficiently
- Minimal repaints on theme changes

## Future Considerations
When adding new features:
1. Check if existing variables cover the use case
2. Add new variables to `variables.css` if needed
3. Document any new variables here
4. Test with extreme user settings (min/max values)
5. Ensure mobile browser compatibility

## Updates Integration
The theme system works with the updates.log system to show recent changes. Style updates should be logged with entries like:
```
[timestamp]|🤖|Enhanced theme system with dynamic shadows and accent glows
```