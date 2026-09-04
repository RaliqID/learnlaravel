---
name: LaraNews 2026
colors:
  surface: '#111317'
  surface-dim: '#111317'
  surface-bright: '#37393d'
  surface-container-lowest: '#0c0e11'
  surface-container-low: '#1a1c1f'
  surface-container: '#1e2023'
  surface-container-high: '#282a2d'
  surface-container-highest: '#333538'
  on-surface: '#e2e2e6'
  on-surface-variant: '#c3c6d7'
  inverse-surface: '#e2e2e6'
  inverse-on-surface: '#2f3034'
  outline: '#8d90a1'
  outline-variant: '#424655'
  surface-tint: '#b3c5ff'
  primary: '#b3c5ff'
  on-primary: '#002a76'
  primary-container: '#1d63ed'
  on-primary-container: '#eeefff'
  inverse-primary: '#0054d8'
  secondary: '#c4c6d0'
  on-secondary: '#2d3038'
  secondary-container: '#464951'
  on-secondary-container: '#b6b8c1'
  tertiary: '#c6c6cd'
  on-tertiary: '#2e3036'
  tertiary-container: '#6c6d73'
  on-tertiary-container: '#f0f0f7'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b3c5ff'
  on-primary-fixed: '#00184a'
  on-primary-fixed-variant: '#003fa5'
  secondary-fixed: '#e0e2ec'
  secondary-fixed-dim: '#c4c6d0'
  on-secondary-fixed: '#191c22'
  on-secondary-fixed-variant: '#44474e'
  tertiary-fixed: '#e2e2e9'
  tertiary-fixed-dim: '#c6c6cd'
  on-tertiary-fixed: '#1a1b21'
  on-tertiary-fixed-variant: '#45474c'
  background: '#111317'
  on-background: '#e2e2e6'
  surface-variant: '#333538'
typography:
  display-xl:
    fontFamily: Geist
    fontSize: 72px
    fontWeight: '600'
    lineHeight: '1.1'
    letterSpacing: -0.04em
  headline-lg:
    fontFamily: Geist
    fontSize: 48px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: -0.03em
  headline-lg-mobile:
    fontFamily: Geist
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Geist
    fontSize: 24px
    fontWeight: '500'
    lineHeight: '1.4'
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Geist
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.8'
    letterSpacing: '0'
  body-md:
    fontFamily: Geist
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
    letterSpacing: '0'
  label-md:
    fontFamily: Geist
    fontSize: 14px
    fontWeight: '500'
    lineHeight: '1.4'
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Geist
    fontSize: 12px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  container-max: 1280px
  reading-width: 720px
  gutter: 24px
  margin-mobile: 20px
  section-gap: 80px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
---

## Brand & Style

The brand personality is authoritative yet approachable, positioning itself as a premium source for technology and culture. It targets a discerning audience that values clarity, intellectual depth, and a high-signal-to-noise ratio. The UI should evoke a sense of "quiet confidence"—calm, sophisticated, and technically precise.

This design system utilizes a **Minimalist Corporate** style with **high-end editorial** influences. It rejects the "bubbly" trends of consumer tech in favor of structured precision. Key characteristics include:
- **Atmospheric Depth:** Relying on tonal layering rather than shadows.
- **Precision:** Using 1px hairlines to define structure.
- **Focus:** Massive editorial typography that commands attention, paired with generous negative space to reduce cognitive load.
- **Premium Utility:** A developer-centric cleanliness (inspired by Linear) applied to digital journalism.

## Colors

The palette is anchored in a deep, monochromatic spectrum to ensure content remains the focal point.

- **Foundations:** The primary background is a near-black charcoal (`#0d0f12`). Surface elevation is achieved through two higher-value tiers: `#16181d` for secondary containers and `#1c1f26` for active or highlighted interactive elements.
- **Typography:** Soft white (`#eef0f2`) provides high contrast without the eye strain of pure white. Muted gray (`#8a8d91`) is reserved for metadata, captions, and secondary information.
- **Accent:** A single, controlled "LaraNews Blue" (`#1d63ed`) is used sparingly for primary actions, progress indicators, and subtle brand reinforcement.
- **Borders:** Structural hairlines use a low-contrast gray-blue (`#26292f`) to define boundaries without breaking the visual flow.

## Typography

The typography system uses **Geist** exclusively to achieve a modern, technical, and highly legible aesthetic. 

- **Editorial Presence:** Large-scale headlines (`display-xl`, `headline-lg`) use tight letter spacing and semi-bold weights to create a "wall of text" impact characteristic of premium print magazines.
- **Readability:** Long-form body text is optimized for a reading width of 680-760px. A generous line height of 1.8 for `body-lg` ensures maximum comfort during extended reading sessions.
- **Micro-copy:** Labels and metadata use increased letter-spacing and uppercase styling at smaller sizes (`label-sm`) to maintain clarity against dark backgrounds.

## Layout & Spacing

The design system employs a **fluid-to-fixed grid** hybrid. 

- **Main Grid:** A 12-column system for landing pages and discovery views with a maximum width of 1280px.
- **Article Layout:** A centered, single-column "reading lane" constrained to 720px to prevent horizontal eye fatigue.
- **Rhythm:** Spacing follows a strict 8px base unit. Section gaps are intentionally large (80px+) to facilitate a "breathable" high-end feel.
- **Mobile Adaptivity:** At the 768px breakpoint, margins shrink to 20px, and vertical stacking becomes the primary layout engine. Complex grids reflow into single-column lists.

## Elevation & Depth

This system eschews traditional shadows in favor of **Tonal Layering** and **Subtle Outlines**.

- **Surface Levels:** Depth is indicated by color value. The background is the darkest layer. Interactive elements like cards or navigation bars sit on a slightly lighter surface (`#16181d`). Hover states or active selections use the next tier (`#1c1f26`).
- **Outlines:** All containers and separators use 1px hairlines. On hover, these borders may subtly brighten to indicate interactivity.
- **Glassmorphism:** Reserved strictly for the global navigation bar. A high-density backdrop blur (20px+) with a semi-transparent dark fill maintains context of the content scrolling beneath while keeping the UI feeling light.

## Shapes

The shape language is disciplined and geometric.

- **Soft Square Aesthetic:** A base roundedness of `0.25rem` (Soft) is applied to buttons and input fields to prevent them from feeling "aggressive," while maintaining the architectural look.
- **Images:** Media assets (thumbnails, hero images) should remain sharp (0px) or use the same subtle 4px radius to align with the grid.
- **Large Components:** Avoid "pill" shapes unless used for small, utility-style chips (e.g., category tags).

## Components

- **Buttons:** Primary buttons use the LaraNews Blue background with white text. Secondary buttons use a subtle 1px border and no fill. All buttons have a fixed height (40px or 48px) and Geist Medium typography.
- **Cards:** Cards are defined by 1px borders rather than shadows. There is no heavy padding between multiple cards; they should feel like part of a unified grid.
- **Input Fields:** Minimalist design with a 1px border and `#0d0f12` background. Focus states are indicated by the accent blue border.
- **Metadata Chips:** Small, uppercase labels with slightly increased letter spacing. Used for categories like "TECH" or "POLICY."
- **Progressive Disclosure:** Use thin chevron icons (2px stroke) for accordion and dropdown interactions to maintain the "linear" technical feel.
- **Logo:** The LaraNews "L" mark should be treated as a high-contrast premium element, usually rendered in pure white or the accent blue against the dark background.