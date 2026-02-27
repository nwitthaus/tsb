# Trivia Scoreboard Design System

This document defines the visual language for the Trivia Scoreboard application. It serves as the single source of truth when styling pages and components.

The system uses a **dual-tone** approach:

- **Public tone** — Bold, geometric, industrial. Used on all public-facing pages (homepage, scoreboard, auth).
- **Admin tone** — Clean, functional, neutral. Used on authenticated pages (dashboard, event management, settings). Inherits the color palette and typography from the brand but applies them with restraint via Flux UI components.

---

## 1. Brand Identity

**Aesthetic:** Geometric Bold — structured, graphic, high-contrast, grid-disciplined.

**Principles:**
- Heavy borders over shadows. Flat surfaces over gradients.
- Red is a sharp accent, never a dominant fill. Near-black carries the visual weight.
- Uppercase condensed type for impact. Generous letter-spacing for labels.
- Sharp corners on public pages. Rounded corners on admin (Flux default).
- Centered layouts on public pages. Sidebar/content layouts on admin.

---

## 2. Color Palette

### Brand Colors

| Name | Value | Tailwind | Usage |
|------|-------|----------|-------|
| Near-black | `#141414` | `text-[#141414]` / `border-[#141414]` | Primary text, heavy borders (public) |
| Red | `#DC2626` | `red-600` | Accent — buttons, badges, highlights |
| Red hover | `#B91C1C` | `red-700` | Hover states on red elements |
| Cream | `#F2F2F0` | `bg-[#F2F2F0]` | Public page background |
| White | `#FFFFFF` | `bg-white` | Form surfaces, cards |

### Neutral Scale (Admin)

The admin tone uses the custom zinc scale defined in `app.css`:

| Token | Value | Usage |
|-------|-------|-------|
| `zinc-50` | `#fafafa` | Lightest backgrounds |
| `zinc-100` | `#f5f5f5` | Subtle backgrounds |
| `zinc-200` | `#e5e5e5` | Borders (light mode) |
| `zinc-300` | `#d4d4d4` | Disabled borders |
| `zinc-400` | `#a3a3a3` | Placeholder text |
| `zinc-500` | `#737373` | Secondary text |
| `zinc-600` | `#525252` | Body text (light mode) |
| `zinc-700` | `#404040` | Borders (dark mode) |
| `zinc-800` | `#262626` | Surfaces (dark mode) |
| `zinc-900` | `#171717` | Backgrounds (dark mode) |
| `zinc-950` | `#0a0a0a` | Deepest dark background |

### Functional Colors

| Purpose | Color | Tailwind | Context |
|---------|-------|----------|---------|
| Filled score | Emerald | `emerald-50` bg, `emerald-300` border | Scoring grid cells with values |
| Rank #1 | Red | `red-600` | First place in scoreboard |
| Rank #2 | Slate | `slate-500` | Second place in scoreboard |
| Rank #3 | Amber | `amber-500` | Third place in scoreboard |
| Links | Blue | `blue-600` / `dark:blue-400` | Admin page links |
| Destructive | Red | Via Flux `variant="danger"` | Delete actions |

### Muted Text (Public)

| Name | Value | Usage |
|------|-------|-------|
| Secondary text | `#7A7A7A` | Subtitles, labels, helper text |
| Placeholder | `#B0B0B0` | Input placeholder text |

---

## 3. Typography

### Font Families

Loaded from [Bunny Fonts](https://fonts.bunny.net) in `partials/head.blade.php`.

| Token | Font | Tailwind Class | Role |
|-------|------|----------------|------|
| `--font-heading` | Oswald | `font-heading` | Display headings (public pages) |
| `--font-grotesk` | Space Grotesk | `font-grotesk` | Body text (public pages) |
| `--font-mono` | Space Mono | `font-mono` | Join codes, monospaced inputs |
| `--font-display` | Archivo Black | `font-display` | Scoreboard headlines |
| `--font-body` | Outfit | `font-body` | Scoreboard body text |
| `--font-sans` | Instrument Sans | `font-sans` | Admin UI (Flux default) |

### Type Scale (Public Tone)

| Element | Size | Weight | Tracking | Classes |
|---------|------|--------|----------|---------|
| Page heading | 56–64px | 700 | tight | `font-heading text-[56px] sm:text-[64px] font-bold uppercase leading-[0.95] tracking-tight` |
| Section label | 10px | 500 | 0.25em | `text-[10px] font-medium uppercase tracking-[0.25em] text-[#7A7A7A]` |
| Body copy | 15px | 400 | normal | `text-[15px] leading-relaxed text-[#7A7A7A]` |
| Button text | 13px | 700 | 0.1em | `font-heading text-[13px] font-bold uppercase tracking-[0.1em]` |
| Input text | 13px | 400 | 0.1em | `font-mono text-[13px] uppercase tracking-[0.1em]` |
| Stat number | text-4xl | 700 | — | `font-heading text-4xl font-bold` |
| Stat label | 10px | 500 | 0.15em | `text-[10px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]` |
| Nav brand | 12px | 700 | 0.2em | `font-heading text-xs font-bold uppercase tracking-[0.2em]` |

### Type Scale (Admin Tone)

Use Flux UI default sizing. Key overrides:

| Element | Classes |
|---------|---------|
| Page title | `<flux:heading size="lg">` |
| Section title | `<flux:heading size="sm">` or `<flux:subheading>` |
| Body text | Default (no class needed) |
| Table text | `text-sm` |

---

## 4. Spacing & Layout

### Public Pages

| Property | Value | Notes |
|----------|-------|-------|
| Max-width (hero) | `max-w-[640px]` | Centered with `mx-auto` |
| Horizontal padding | `px-8` | Consistent on all sections |
| Top padding (hero) | `pt-[72px]` | Generous breathing room above headline |
| Bottom padding (hero) | `pb-12` | |
| Form container width | `max-w-[440px]` | Centered within hero |
| Stats vertical padding | `py-6` | Per stat cell |
| Gap between label → heading | `mb-5` | |
| Gap between heading → body | `mt-5` | |
| Gap between body → form | `mt-11` | Larger gap before the CTA |
| Gap between form → helper | `mt-5` | |

### Admin Pages

| Property | Value | Notes |
|----------|-------|-------|
| Vertical rhythm | `space-y-6` | Between major sections |
| Table cell padding | `py-2.5` (body), `py-2` (header) | Via Flux `<flux:table>` |
| Form field gap | `gap-2` | Via Flux `<flux:field>` |
| Flex gaps | `gap-2`, `gap-4`, `gap-6` | Contextual |

### Scoreboard

| Property | Value |
|----------|-------|
| Max-width | `max-w-4xl` |
| Padding | `px-4 py-8` |

---

## 5. Borders & Surfaces

### Public Tone

Heavy borders define structure. No shadows.

| Element | Border | Background |
|---------|--------|------------|
| Top bar divider | `border-b-2 border-[#141414]` | — |
| Form container | `border-2 border-[#141414]` | `bg-white` |
| Form input | `border-2 border-[#141414]` | `bg-transparent` |
| Stats section | `border-y-2 border-[#141414]` | — |
| Stats column divider | `border-r-2 border-[#141414]` | — |
| Page background | — | `bg-[#F2F2F0] bg-grid-subtle` |

**Key rule:** No `rounded-*` classes on public-tone borders. Sharp corners only.

### Admin Tone

Subtle borders with rounded corners. Shadows via Flux.

| Element | Border | Radius |
|---------|--------|--------|
| Cards | `border border-neutral-200` | `rounded-xl` |
| Inputs | `border border-neutral-300` | `rounded` |
| Tables | `border-b border-neutral-200` | — |
| Dashed empty states | `border-dashed border-neutral-300` | `rounded` |

### Scoreboard

| Element | Border |
|---------|--------|
| Header | `rounded-t-xl` with gradient bg |
| Table body | `rounded-b-xl` |
| Round columns | `border-l-3 border-neutral-200` |
| Header accent | `border-b-2 border-red-600` |

---

## 6. Component Patterns

Copy-pasteable Tailwind class recipes for common elements.

### Buttons

**Public — Primary (red CTA):**
```html
<button class="border-2 border-red-600 bg-red-600 px-6 py-3.5 font-heading text-[13px] font-bold uppercase tracking-[0.1em] text-white transition-colors hover:border-red-700 hover:bg-red-700">
    JOIN
</button>
```

**Public — Text link:**
```html
<a class="font-bold text-[#141414] underline hover:text-red-600">Log in</a>
```

**Admin — Use Flux components:**
```html
<flux:button>Primary</flux:button>
<flux:button variant="danger">Delete</flux:button>
<flux:button variant="ghost">Cancel</flux:button>
```

### Form Inputs

**Public — Monospace code input:**
```html
<input class="flex-1 border-2 border-r-0 border-[#141414] bg-transparent px-4 py-3.5 font-mono text-[13px] uppercase tracking-[0.1em] text-[#141414] placeholder-[#B0B0B0] outline-none transition-colors focus:border-red-600 focus:border-r-0" />
```

**Admin — Use Flux components:**
```html
<flux:input wire:model="name" label="Team name" />
```

### Badges

**Public — LIVE badge:**
```html
<span class="bg-red-600 px-2 py-0.5 text-[10px] font-bold tracking-[0.1em] text-white">LIVE</span>
```

**Admin — Use Flux badge:**
```html
<flux:badge color="red">Active</flux:badge>
```

### Stats Display (Public)

```html
<div class="border-y-2 border-[#141414]">
    <div class="flex">
        <div class="flex-1 border-r-2 border-[#141414] py-6 text-center">
            <div class="font-heading text-4xl font-bold text-[#141414]">1,247</div>
            <div class="mt-1 text-[10px] font-medium uppercase tracking-[0.15em] text-[#7A7A7A]">Events</div>
        </div>
        <!-- ... more columns ... -->
    </div>
</div>
```

### Section Label (Public)

```html
<div class="mb-5 text-[10px] font-medium uppercase tracking-[0.25em] text-[#7A7A7A]">
    &mdash;&mdash; Section Title &mdash;&mdash;
</div>
```

### Page Container (Public)

```html
<div class="bg-grid-subtle relative min-h-screen overflow-hidden bg-[#F2F2F0] font-grotesk text-[#141414]">
    <!-- content -->
</div>
```

### Top Bar (Public)

```html
<div class="relative border-b-2 border-[#141414] py-3.5 text-center">
    <span class="font-heading text-xs font-bold uppercase tracking-[0.2em]">Trivia Scoreboard</span>
    <span class="ml-3 bg-red-600 px-2 py-0.5 text-[10px] font-bold tracking-[0.1em] text-white">LIVE</span>
</div>
```

---

## 7. Tone Reference

Quick lookup: which tone and key classes to use for each page type.

| Page | Tone | Background | Font Stack | Borders | Dark Mode |
|------|------|------------|------------|---------|-----------|
| Homepage | Public | `bg-[#F2F2F0] bg-grid-subtle` | `font-grotesk` + `font-heading` | 2px `border-[#141414]` | No |
| Scoreboard | Public | `bg-neutral-100` | `font-body` + `font-display` | Mixed (gradient header, subtle table) | No |
| Auth (login/register) | Public | `bg-neutral-100` | Flux defaults | `rounded-xl`, subtle | No |
| Dashboard | Admin | Flux default | Flux defaults (`font-sans`) | Flux default | Yes |
| Event management | Admin | Flux default | Flux defaults | Flux default | Yes |
| Settings | Admin | Flux default | Flux defaults | Flux default | Yes |

### When building a new page:

1. **Public page?** Start with the Public container pattern (Section 6). Use `font-grotesk` for body, `font-heading` for headings. Heavy 2px borders. No rounded corners. Center the layout at `max-w-[640px]`.
2. **Admin page?** Use Flux components. Dark mode enabled. No custom border styling needed. Follow the existing dashboard/event pages as reference.
3. **Scoreboard/display page?** Use `font-body` + `font-display`. Red gradient headers. Clean table styling with subtle borders.

---

## 8. Custom CSS Utilities

Defined in `resources/css/app.css`:

| Class | Purpose |
|-------|---------|
| `.bg-grid-subtle` | 48px grid pattern at 3% opacity — use on public page backgrounds |
| `.animate-ticker` | Horizontal gradient scroll animation (reserved) |
| `.animate-pulse-dot` | Pulsing opacity animation for live indicators |

---

## 9. Assets & Configuration

**Font loading:** `resources/views/partials/head.blade.php` — all fonts loaded from Bunny Fonts.

**Theme tokens:** `resources/css/app.css` — `@theme` block defines font families and zinc color scale.

**Flux accent colors:** `--color-accent` maps to `neutral-800` (light) / `white` (dark). This controls Flux component accent colors and should not be overridden per-page.
