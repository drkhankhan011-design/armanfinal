/**
 * Browser-persistence policy.
 *
 * This file is intentionally small and dependency-free so it can be used by
 * CI or a pre-commit hook. A frontend business-data path must call a domain
 * service, never a browser storage API.
 */

const FORBIDDEN_BROWSER_PERSISTENCE = [
  "localStorage",
  "sessionStorage",
  "indexedDB",
  "medicare_",
  "patients_",
  "patient_registry",
  "appointments",
  "prescriptions",
  "clinicalNotes",
  "vitals_",
  "payments",
  "audit_log",
  "drugReminders_",
  "medAdminRecord_",
] as const;

export function isForbiddenBrowserPersistenceReference(source: string): boolean {
  return FORBIDDEN_BROWSER_PERSISTENCE.some((token) => source.includes(token));
}

export function assertServerOwnedDataPath(source: string, filePath = "frontend"): void {
  if (isForbiddenBrowserPersistenceReference(source)) {
    throw new Error(
      `${filePath}: business data must use a domain service and the PHP API; browser persistence is forbidden`,
    );
  }
}
