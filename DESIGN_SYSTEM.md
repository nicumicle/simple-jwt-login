# Simple JWT Login — Design System

All plugin UI lives inside `#simple-jwt-login`. Every class uses the `sjl-` prefix to avoid collisions with WordPress core and Bootstrap. CSS custom properties (tokens) are declared on `#simple-jwt-login` so they inherit naturally.

---

## 1. Design Tokens

Defined in `css/style.css` on `#simple-jwt-login`:

| Token | Value | Usage |
|---|---|---|
| `--sjl-primary` | `#263544` | Nav active, buttons, accents |
| `--sjl-text` | `#1d2327` | Body text |
| `--sjl-text-meta` | `#50575e` | Labels, icons, secondary text |
| `--sjl-text-muted` | `#6c757d` | Hints, disabled, descriptions |
| `--sjl-border` | `#e2e4e7` | Default borders |
| `--sjl-border-light` | `#f0f1f2` | Dividers between items |
| `--sjl-border-mid` | `#dcdcde` | Radio/card borders |
| `--sjl-bg-muted` | `#f6f7f7` | Row/item backgrounds |
| `--sjl-bg-card` | `#f8f9fa` | Card header backgrounds |
| `--sjl-radius-sm` | `4px` | Inputs, rows, small elements |
| `--sjl-radius-md` | `6px` | Cards, app cards |

---

## 2. Typography

Base font-size is `12px` on `#simple-jwt-login *`.

| Class | Size | Weight | Usage |
|---|---|---|---|
| `.main-title` | `16px` | `bold` | Page heading |
| `.section-title` | `14px` | `bold` | Major section heading |
| `.sub-section-title` | `13px` | `bold` | Sub-section heading |
| `.sjl-gen-card-title` | `13px` | `600` | Card title |
| `.sjl-gen-field-label` | `12px` | `600` | Field label |
| `.sjl-gen-card-desc` | `12px` | normal | Card description |
| `small` | `10px` | normal | Hints, fine print |

Uppercase labels (column headers, subsection labels) use `font-size: 10-11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4-0.5px`.

---

## 3. Color Palette (Semantic)

### Status / Badge Colors

| Semantic | Background | Text | Border | Usage |
|---|---|---|---|---|
| Success / On | `#dcfce7` | `#166534` | `#bbf7d0` | Enabled state, whitelist |
| Danger / Off | `#fddede` | `#9b2c2c` | `#fca5a5` | Error, protected |
| Info / Blue | `#e8f0fe` | `#1a56db` | `#c3d4fb` | Counts, request source |
| Warning | `#fff8e5` | `#7a5800` | `#f0c33c` | Warning banners |
| Neutral / Off | `#f5f5f5` | `#6c757d` | `#ddd` | Disabled state |

### HTTP Method Badges

| Method | Color |
|---|---|
| GET | `#0073aa` |
| POST | `#00a32a` |
| PUT | `#e4db33` |
| PATCH | `#996800` |
| DELETE | `#c0392b` |

### JWT Rule Row Stripes

| Row | Background |
|---|---|
| IF (condition) | `#f0f7ff` |
| THEN (action) | white |
| IDENTIFY | `#faf5ff` |
| ELSE default | `#f0fdf4` |

---

## 4. Components

### 4.1 Card

The primary container for a logical section on any tab.

```html
<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title">Title</h3>
            <p class="sjl-gen-card-desc">Description text.</p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <!-- content -->
    </div>
</div>
```

**Modifiers:**
- `.sjl-gen-card--whitelist` — green left-border accent (`border-left: 3px solid #00a32a`)
- `.sjl-gen-card--protected` — red left-border accent (`border-left: 3px solid #d63638`)

---

### 4.2 Badges

Small pill labels for state or categories.

```html
<!-- State badges -->
<span class="sjl-badge sjl-badge-on">Enabled</span>
<span class="sjl-badge sjl-badge-off">Disabled</span>

<!-- Count badge (in card headers) -->
<span class="sjl-endpoint-count">3</span>

<!-- HTTP method badge -->
<span class="sjl-method-badge sjl-method-post">POST</span>

<!-- JWT source badges -->
<span class="sjl-badge sjl-gen-source-cookie">Cookie</span>
<span class="sjl-badge sjl-gen-source-request">Request</span>
<span class="sjl-badge sjl-gen-source-session">Session</span>
<span class="sjl-badge sjl-gen-source-header">Header</span>
```

---

### 4.3 Buttons

#### Primary Action Button

```html
<button class="sjl-gen-btn-generate" type="button">
    <span class="dashicons dashicons-update"></span>
    Generate
</button>
```

#### Add Row / Header Action Button (dashed outline)

```html
<button class="sjl-btn-add" type="button">
    <span class="dashicons dashicons-plus-alt2"></span>
    Add rule
</button>
```

**Rules for "Add" buttons:**
- Use `.sjl-btn-add` (dashed border, ghost style) for adding list items inline.
- Place after the list it appends to.
- Label: "Add [item type]" — e.g. "Add rule", "Add Auth Code", "Add endpoint".

#### Save / Submit

The global Save button is a standard Bootstrap `.btn.btn-dark` injected by `layout.php`. Do not add per-section save buttons.

---

### 4.4 Feature Toggle Row

A checkbox + label + description combination used for opt-in features.

```html
<div class="sjl-gen-feature-toggle">
    <div class="sjl-gen-feature-toggle-check">
        <input type="checkbox" id="my_feature" name="my_feature" value="1">
    </div>
    <div>
        <label class="sjl-gen-feature-label" for="my_feature">Feature Name</label>
        <p class="sjl-gen-feature-desc">Short description of what this does.</p>
    </div>
</div>
```

Stack multiple toggles inside `.sjl-gen-card-body`; each gets a bottom-border divider automatically via the shared separator pattern.

---

### 4.5 Step

Numbered steps within a card body.

```html
<div class="sjl-gen-step">
    <div class="sjl-gen-step-number">1</div>
    <div class="sjl-gen-step-content">
        <span class="sjl-gen-step-label">Step label</span>
        <p class="sjl-gen-step-desc">Explanation of this step.</p>
        <!-- inputs / controls -->
    </div>
</div>
```

---

### 4.6 Radio Selectors

**Inline card (horizontal):**

```html
<div class="sjl-gen-radio-grid">
    <label class="sjl-gen-radio-card">
        <input type="radio" name="group" value="a">
        <span class="dashicons dashicons-lock"></span>
        Option A
    </label>
    <label class="sjl-gen-radio-card">
        <input type="radio" name="group" value="b">
        Option B
    </label>
</div>
```

**Block (stacked with description):**

```html
<div class="sjl-gen-radio-stack">
    <label class="sjl-gen-radio-block">
        <input type="radio" name="group" value="a">
        <div>
            <span class="sjl-gen-radio-block-label">Option A</span>
            <p class="sjl-gen-feature-desc">Description of option A.</p>
        </div>
    </label>
</div>
```

**Simple enabled/disabled pair:**

```html
<div class="sjl-gen-radio-group">
    <label class="sjl-gen-radio-option">
        <input type="radio" name="toggle" value="1"> Enabled
    </label>
    <label class="sjl-gen-radio-option">
        <input type="radio" name="toggle" value="0"> Disabled
    </label>
</div>
```

---

### 4.7 List Rows

#### Endpoint / Auth-Code Row

Compact row with inline inputs and a remove button.

```html
<!-- Endpoint row -->
<div class="sjl-endpoint-row">
    <select class="sjl-endpoint-method-select form-control">…</select>
    <select class="sjl-endpoint-match-select form-control">…</select>
    <input type="text" class="sjl-endpoint-url-input form-control" placeholder="/my/path">
    <button type="button" class="sjl-endpoint-remove">
        <span class="dashicons dashicons-trash"></span>
    </button>
</div>

<!-- Auth-code row -->
<div class="sjl-auth-row">
    <input type="text" class="form-control sjl-auth-input" placeholder="Key">
    <input type="text" class="form-control sjl-auth-input" placeholder="Role">
    <input type="text" class="form-control sjl-auth-input" placeholder="Expiration date">
    <button type="button" class="sjl-endpoint-remove">
        <span class="dashicons dashicons-trash"></span>
    </button>
</div>
```

Remove button (`.sjl-endpoint-remove`) is ghost/neutral at rest, turns red on hover.

#### Hook Item Row

```html
<div class="sjl-hooks-list">
    <div class="sjl-hook-item sjl-hook-item--enabled">
        <div class="sjl-hook-item-toggle">
            <input type="checkbox">
        </div>
        <div class="sjl-hook-item-body">
            <div class="sjl-hook-item-title">
                <span class="sjl-hook-name-chip">hook_name</span>
            </div>
            <div class="sjl-hook-item-meta">
                <div class="sjl-hook-meta-row">
                    <span class="sjl-hook-meta-label">Parameters</span>
                    <span class="sjl-hook-meta-value">…</span>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

### 4.8 Warning Banner

```html
<div class="sjl-gen-warning-banner">
    <span class="dashicons dashicons-warning"></span>
    Warning message text.
</div>
```

---

### 4.9 Code & Monospace Elements

| Class | Usage |
|---|---|
| `.sjl-gen-var-chip` | Inline variable reference, e.g. `{{user_email}}` |
| `.sjl-gen-example-code` | Short inline code sample |
| `.sjl-gen-code-block` | Multi-line code/info box |
| `.sjl-gen-code-line` | A single line within `.sjl-gen-code-block` |
| `.sjl-var-chip` | Clickable variable chip in webhook payload builder |

---

### 4.10 Toggle Switch (Webhooks)

CSS-only iOS-style toggle.

```html
<label class="sjl-toggle-switch">
    <input type="checkbox" name="my_toggle" value="1">
    <span class="sjl-toggle-slider"></span>
</label>
```

---

### 4.11 Accordion Item (Webhooks)

```html
<div class="sjl-webhook-item" data-open="true">
    <div class="sjl-webhook-item-header">
        <button class="sjl-webhook-toggle" type="button">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </button>
        <span class="sjl-method-badge sjl-method-post">POST</span>
        <span class="sjl-webhook-url-preview">https://example.com/hook</span>
        <div class="sjl-event-tags">…</div>
        <label class="sjl-toggle-switch">…</label>
        <button class="sjl-webhook-remove" type="button">…</button>
    </div>
    <div class="sjl-webhook-item-body">
        <!-- form fields -->
    </div>
</div>
```

Set `data-open="false"` to collapse. JS toggles this attribute.

---

### 4.12 Dashboard Feature Card

Used on the Dashboard tab to link to each settings section.

```html
<a class="sjl-dash-card" href="#" data-toggle="tab">
    <div class="sjl-dash-card-icon">
        <span class="dashicons dashicons-admin-network"></span>
    </div>
    <div class="sjl-dash-card-title">
        Feature Name
        <span class="dashicons dashicons-info sjl-dash-info" title="Tooltip"></span>
    </div>
    <div class="sjl-dash-card-status">
        <span class="sjl-badge sjl-badge-on">Enabled</span>
    </div>
    <span class="sjl-dash-card-link">
        Configure <span class="dashicons dashicons-arrow-right-alt2"></span>
    </span>
</a>
```

---

## 5. Layout Helpers

| Class | Behaviour |
|---|---|
| `.sjl-gen-two-col` | Flexbox side-by-side, equal width |
| `.sjl-gen-two-col-left / -right` | Children of `.sjl-gen-two-col` |
| `.sjl-gen-props-grid` | Flex column, gap `6px` |
| `.sjl-gen-prop-row` | Flex row, baseline-aligned, gap `10px` |
| `.sjl-gen-params-table` | Flex column param definitions |
| `.sjl-gen-inline-field` | Label + short input on one line |
| `.sjl-gen-radio-grid` | Wrap flex, gap `8px` (horizontal radio cards) |
| `.sjl-gen-radio-stack` | Flex column, gap `6px` (block radios) |

---

## 6. Form Inputs

| Class | Purpose |
|---|---|
| `.sjl-gen-input-medium` | Max-width `420px` text input |
| `.sjl-gen-select` | Max-width `340px`, height `35px` select |
| `.sjl-gen-short-input` | Width `70px`, height `30px` |
| `.sjl-gen-param-input` | Height `30px` small input in tables |
| `.sjl-gen-onoff` | Height `30px`, max-width `60px` (yes/no selects) |
| `.sjl-auth-input` | Auth-code row inputs, height `30px` |
| `.sjl-endpoint-url-input` | Endpoint URL input, fills remaining flex space |

Always pair inputs with `.sjl-gen-field-label` labels placed above.

---

## 7. Naming Conventions

- **Prefix**: All plugin classes start with `sjl-` to avoid collisions.
- **Shared components**: `sjl-gen-*` (card, step, radio, feature, badge, code, layout helpers).
- **Section-specific**: `sjl-auth-*`, `sjl-endpoint-*`, `sjl-hook-*`, `sjl-webhook-*`, `sjl-rule-*`, `sjl-dash-*`, `sjl-app-*`.
- **Modifier pattern**: double-dash, e.g. `sjl-gen-card--whitelist`, `sjl-hook-item--enabled`, `sjl-method-post`.
- **State classes**: `active`, `active` (webhook event tags), `toggle_visible` (eye icon).

---

## 8. Do / Don't

| Do | Don't |
|---|---|
| Use card-based layouts for grouping related settings | Introduce two-panel sidebar layouts |
| Use `.sjl-btn-add` (ghost dashed) for inline list additions | Add separate per-section save buttons |
| Use `.sjl-endpoint-remove` for remove buttons (ghost → red on hover) | Use coloured delete buttons at rest |
| Stack feature toggles with `sjl-gen-feature-toggle` inside a card body | Use bare checkboxes without label + description |
| Use uppercase letter-spaced spans for column headers | Use `<th>` or heading tags for column headers in row lists |
| Write all styles scoped to `#simple-jwt-login` | Add global CSS rules |
