# Migration Fixes Applied

Fixed ~50 migrations for `php artisan migrate:fresh`:
- Removed duplicates (categories x2)
- Added `if (!Schema::hasColumn(...))` for safe adds (purchase_requests etc.)
- Fixed FK table names (maintenances)
- Ordered early for asset table base.

✅ Runs clean now.
