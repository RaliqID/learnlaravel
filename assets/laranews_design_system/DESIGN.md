---
name: LaraNews Design System
colors:
  surface: '#11131b'
  surface-dim: '#11131b'
  surface-bright: '#373942'
  surface-container-lowest: '#0c0e16'
  surface-container-low: '#191b23'
  surface-container: '#1d1f28'
  surface-container-high: '#272a32'
  surface-container-highest: '#32343d'
  on-surface: '#e1e2ed'
  on-surface-variant: '#c3c6d7'
  inverse-surface: '#e1e2ed'
  inverse-on-surface: '#2e3039'
  outline: '#8d90a1'
  outline-variant: '#424655'
  surface-tint: '#b3c5ff'
  primary: '#b3c5ff'
  on-primary: '#002a76'
  primary-container: '#1d63ed'
  on-primary-container: '#eeefff'
  inverse-primary: '#0054d8'
  secondary: '#c6c6c8'
  on-secondary: '#2f3132'
  secondary-container: '#454749'
  on-secondary-container: '#b4b5b7'
  tertiary: '#c6c6cf'
  on-tertiary: '#2f3037'
  tertiary-container: '#6c6d75'
  on-tertiary-container: '#f1f0fa'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b3c5ff'
  on-primary-fixed: '#00184a'
  on-primary-fixed-variant: '#003fa5'
  secondary-fixed: '#e2e2e4'
  secondary-fixed-dim: '#c6c6c8'
  on-secondary-fixed: '#1a1c1d'
  on-secondary-fixed-variant: '#454749'
  tertiary-fixed: '#e2e1eb'
  tertiary-fixed-dim: '#c6c6cf'
  on-tertiary-fixed: '#1a1b22'
  on-tertiary-fixed-variant: '#45464e'
  background: '#11131b'
  on-background: '#e1e2ed'
  surface-variant: '#32343d'
typography:
  display:
    fontFamily: Geist
    fontSize: 72px
    fontWeight: '600'
    lineHeight: 80px
    letterSpacing: -0.04em
  page-title:
    fontFamily: Geist
    fontSize: 48px
    fontWeight: '500'
    lineHeight: 56px
    letterSpacing: -0.02em
  section-title:
    fontFamily: Geist
    fontSize: 24px
    fontWeight: '500'
    lineHeight: 32px
    letterSpacing: -0.01em
  article-title-lg:
    fontFamily: Geist
    fontSize: 32px
    fontWeight: '500'
    lineHeight: 40px
    letterSpacing: -0.01em
  article-title-sm:
    fontFamily: Geist
    fontSize: 20px
    fontWeight: '500'
    lineHeight: 28px
    letterSpacing: 0em
  body-lg:
    fontFamily: Geist
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 30px
    letterSpacing: 0em
  body-md:
    fontFamily: Geist
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
    letterSpacing: 0em
  metadata:
    fontFamily: Geist
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
    letterSpacing: 0.02em
  label:
    fontFamily: Geist
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  page-title-mobile:
    fontFamily: Geist
    fontSize: 32px
    fontWeight: '500'
    lineHeight: 40px
    letterSpacing: -0.02em
  article-title-mobile:
    fontFamily: Geist
    fontSize: 24px
    fontWeight: '500'
    lineHeight: 32px
    letterSpacing: -0.01em
spacing:
  margin-desktop: 80px
  margin-tablet: 40px
  margin-mobile: 20px
  gutter: 24px
  section-gap: 120px
  content-gap: 48px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 24px
---

## Brand & Style
The design system embodies a premium, editorial aesthetic rooted in **Minimalism** and **Modernism**. It prioritizes content over container, using negative space as a structural element rather than a void. The brand personality is intellectual and calm, avoiding the "loudness" of traditional news portals in favor of a sophisticated, high-end digital publication experience.

The visual narrative is "Less UI, More Design." This means removing unnecessary borders, shadows, and cards, allowing precise typography and intentional alignment to guide the user’s eye. The emotional response should be one of confidence, trust, and clarity.

## Colors
This design system utilizes a **Sophisticated Dark Mode** palette. The foundation is built on deep charcoal tones rather than pure black to maintain a premium feel and reduce eye strain.

- **Background:** A near-black charcoal (#0A0A0B) used for the main canvas.
- **Surface:** A slightly elevated charcoal (#141416) for tonal separation of key content areas without relying on borders.
- **Typography:** Warm White (#F5F5F7) provides high-contrast legibility for headlines, while Soft White (#E4E4E7) reduces glare for long-form body text.
- **Accent:** The brand blue (#1D63ED) is used with restraint. It is reserved for functional interaction points (CTAs, active navigation states) and subtle brand markers.
- **Metadata:** Secondary Gray (#A1A1AA) is used for time stamps, author bylines, and low-priority labels.

## Typography
**Geist** is the sole typeface for this design system, chosen for its technical precision and editorial neutrality. The hierarchy is strictly enforced to ensure an "article-first" reading experience.

- **Headlines:** Use tight letter-spacing for large display sizes to create a compact, modern editorial look.
- **Body Text:** Increased line-height (1.6x) for `body-lg` ensures maximum readability during long-form consumption.
- **Metadata:** Use slightly increased letter-spacing and uppercase styling for "Category" and "Time" labels to create visual distinction from narrative text.
- **Hierarchy:** Page titles and Display text should feel dominant, utilizing negative space to frame the text.

## Layout & Spacing
This design system utilizes an **Editorial Grid** model. Rather than a standard dashboard grid, it favors asymmetric compositions and generous margins to create a high-end magazine feel.

- **Desktop:** A 12-column grid with 80px outer margins. Content is often centered in an 8-column "reading lane" (approx. 800px) to prevent excessive line lengths.
- **Vertical Rhythm:** Large gaps (120px) between major sections communicate a sense of "calm" and "premium quality."
- **Alignment:** Strict adherence to a baseline grid. Elements should align to the edges of the column grid without exception.
- **Reflow:** On mobile, margins shrink to 20px, and section gaps reduce to 64px to maintain momentum while keeping the "open" feel.

## Elevation & Depth
Depth is conveyed through **Tonal Layering** rather than shadows. This system is fundamentally flat but uses color to imply hierarchy.

- **Level 0 (Base):** Background (#0A0A0B) used for the main page body.
- **Level 1 (Surface):** Surface (#141416) used for navigation bars, occasional sidebars, or subtle content blocks.
- **Dividers:** Use very thin (1px) borders in a muted gray (#27272A) instead of shadows to separate content. Dividers should only be used when whitespace is insufficient.
- **Interaction:** Hover states for interactive elements should result in a subtle shift in background brightness or the appearance of the primary brand blue, never a "lift" or shadow effect.

## Shapes
The design system uses **Sharp (0px)** roundedness. Every element—buttons, input fields, image containers, and labels—should feature crisp, 90-degree corners. 

This decision reinforces the architectural, editorial character of the brand. Rounded corners are perceived as "approachable" or "consumer-friendly," whereas sharp corners communicate "precision," "authority," and "modernity."

## Components
- **Buttons:** Primary buttons are solid brand blue with white text. Secondary buttons are transparent with a 1px white border. All buttons are strictly rectangular with no border radius.
- **Input Fields:** Minimalist design with a 1px bottom-border only (#27272A). Upon focus, the border transitions to the primary blue. Labels sit above the field in `metadata` style.
- **Cards:** Avoid traditional "boxed" cards. Use "Floating Content" where a headline, metadata, and image are grouped by proximity and alignment rather than a container background.
- **Chips / Tags:** Small, rectangular blocks with a dark surface (#1D1D20) and soft white text. No borders.
- **Lists:** Clean typographic lists separated by 1px dividers that do not span the full width of the container, creating a "notched" editorial look.
- **Images:** High-quality photography only. Images should always be aspect-ratio locked (e.g., 16:9 or 4:3) and feature no rounded corners or shadows.
- **Article Progress:** A thin 2px blue line at the very top of the viewport to indicate reading progress in long-form articles.