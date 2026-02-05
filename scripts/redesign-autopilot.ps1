# ============================================
# SellerMind UI Redesign Autopilot
# ============================================

$ProjectPath = "D:\server\OSPanel\home\sellermind"
Set-Location $ProjectPath

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   UI REDESIGN AUTOPILOT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$prompt = @"
You are a Senior UI/UX Designer. Your task: completely redesign ALL pages in this Laravel project using a unified design system.

## STEP 1: READ DESIGN SYSTEM

First, read the design system file:
- `.claude/agents/ui-designer.md`

If it doesn't exist, use these standards:
- Background: bg-gray-50
- Cards: bg-white rounded-xl shadow-sm border border-gray-200 p-6
- Buttons: Use <x-ui.button> component with variants: primary, secondary, danger, ghost
- Inputs: Use <x-ui.input>, <x-ui.select>, <x-ui.textarea> components
- Badges: Use <x-ui.badge> with variants: success, warning, danger, gray, wildberries, ozon
- Alerts: Use <x-ui.alert> with variants: success, warning, danger, info
- Empty states: Use <x-ui.empty-state>
- Modals: Use <x-ui.modal>
- Typography: text-2xl font-bold for h1, text-lg font-semibold for h2, text-sm for body

## STEP 2: CHECK COMPONENTS

Verify components exist in `resources/views/components/ui/`:
- button.blade.php
- card.blade.php
- input.blade.php
- select.blade.php
- badge.blade.php
- alert.blade.php
- modal.blade.php
- empty-state.blade.php

If missing, create them following the design system.

## STEP 3: REDESIGN IN ORDER

Redesign pages in this order (one by one):

### Phase 1: Layouts (Foundation)
1. resources/views/layouts/app.blade.php
2. resources/views/components/sidebar.blade.php (or pwa-sidebar)
3. resources/views/components/navbar.blade.php (or pwa-top-navbar)

### Phase 2: Dashboard
4. resources/views/pages/dashboard.blade.php (or dashboard/index)

### Phase 3: Main Pages
5. resources/views/pages/products/ (all files)
6. resources/views/pages/marketplace/orders.blade.php
7. resources/views/pages/sales/ (all files)

### Phase 4: Settings & Others
8. resources/views/company/ (settings pages)
9. resources/views/pages/analytics/
10. Other pages

## STEP 4: FOR EACH PAGE

For EVERY page you redesign:

### 4.1 Page Structure
```blade
<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Page Title</h1>
                    <p class="mt-1 text-sm text-gray-500">Description</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Action buttons -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Page Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Content here -->
    </div>
</div>
```

### 4.2 Replace Elements
- Old buttons -> <x-ui.button variant="...">
- Old cards -> <x-ui.card title="...">
- Old inputs -> <x-ui.input label="..." />
- Old selects -> <x-ui.select label="..." />
- Old badges -> <x-ui.badge variant="...">
- Old alerts -> <x-ui.alert variant="...">

### 4.3 Tables
```blade
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Column</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 text-sm text-gray-900">Data</td>
            </tr>
        </tbody>
    </table>
</div>
```

### 4.4 Stats Cards (Dashboard)
```blade
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <x-ui.stat-card title="Total Sales" value="12,345" change="+12%" changeType="increase" />
</div>
```

## STEP 5: COMMIT AFTER EACH PAGE

After redesigning each page:
```bash
git add -A
git commit -m "style(ui): redesign [page-name] with design system"
```

## RULES

1. DO NOT break functionality - only change styles
2. DO NOT remove Alpine.js logic - keep x-data, @click, etc.
3. DO NOT change PHP logic or controllers
4. DO NOT change routes
5. KEEP all existing features working
6. USE consistent spacing: p-6 for cards, gap-6 between elements
7. USE consistent colors from design system only

## AFTER ALL PAGES

When all pages are redesigned:
1. Run: git push origin develop
2. Update TASKS.md - mark redesign task as done
3. Write summary to AUTOPILOT_LOG.md

## START NOW

Begin with layouts/app.blade.php. Work through each page systematically.
Do not ask for permission - just do it.
Commit after each major page/section.

GO!
"@

Write-Host "Starting full UI redesign..." -ForegroundColor Green
Write-Host "This may take 10-30 minutes" -ForegroundColor Yellow
Write-Host ""

# Run Claude with auto permissions
claude --dangerously-skip-permissions -p $prompt

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   REDESIGN COMPLETE" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Check results:" -ForegroundColor Yellow
Write-Host "  git log --oneline -20"
Write-Host "  php artisan serve"
Write-Host ""
