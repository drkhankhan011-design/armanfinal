# Deletion and cleanup candidates

These files were reviewed on the `eliminate-browser-business-storage` branch. They should be deleted only after the referenced imports are removed or replaced.

## Safe candidates after import audit

- `source_build/dr.armankabir-main/src/frontend/src/lib/api.ts`
  - Deprecated re-export of `apiClient.ts`.
  - Current purpose is compatibility for `hybridStorage.ts`.
  - Replace `hybridStorage.ts` imports with `./apiClient`, then delete this file.

- `source_build/dr.armankabir-main/src/frontend/src/lib/clinicalStore.ts`
  - No-op compatibility stubs; clinical data is already served by React Query and PHP services.
  - Delete after confirming no component imports `getClinicalStore` or `saveClinicalStore`.

- `source_build/dr.armankabir-main/src/frontend/src/lib/hybridStorage.ts`
  - Large legacy compatibility layer containing no-op sync queues, canister migration stubs, and legacy clinical-storage names.
  - Delete after all imports are migrated to domain services.

- `source_build/dr.armankabir-main/src/frontend/src/hooks/useAdminSave.ts`
  - Keep the role labels and explicit admin action wrappers if still used.
  - Delete the synchronous registry caches and compatibility exports: `loadRegistry`, `loadPatientRegistry`, `getAuditLog`, `saveRegistry`, `savePatientRegistry`, `enhancedSaveRegistry`, and `enhancedSavePatientRegistry`.
  - Replace callers with `authService`, `staffService`, `patientService`, and React Query invalidation.

## Do not delete yet

- `src/frontend/src/lib/storageAdapter.ts`
  - It is the server-backed preference boundary. It must remain until all allowed preference callers are migrated or removed.

- `src/frontend/src/lib/clinicalIntelligence.ts`
  - Pure presentation/decision-support functions; it does not persist data.

- `src/frontend/src/services/staffData.ts`
  - It uses the PHP API, but its generic `/data/*` endpoints should be verified for backend authorization and ownership before being retained.

## Repository-level cleanup candidates

Review these manually before deletion because they may be needed for deployment or incident recovery:

- Root `fix_*.php` and `fix_*.py` scripts: one-off repair utilities, not runtime code.
- Root `error_log`, `logs/`, and `tmp/`: generated/runtime artifacts; remove from Git and add to `.gitignore` if production needs them.
- `public_html/assets.backup.*`: deployed asset backup; do not serve or version unless rollback is required.
- `public_html/phpmyadmin/`: should not be shipped in the application repository unless operationally required; prefer a separately managed installation.
- `source_build/dr.armankabir-main/README.md`: stale Caffeine export text; replace or remove once the source-build documentation is authoritative.

## Security cleanup required before production

- Rotate any database password or JWT secret that has ever been committed in `public_html/config.php`.
- Remove hardcoded production credentials from tracked files.
- Change seeded default passwords immediately.
- Verify `env.json`, logs, backups, SSL material, and phpMyAdmin are not publicly downloadable.

This list is intentionally conservative: deleting a compatibility file before its import graph is migrated can break the build. Run `npm run audit:storage`, `npm run typecheck`, and `npm run build` after each deletion batch.
