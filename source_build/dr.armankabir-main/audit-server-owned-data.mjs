#!/usr/bin/env node
/**
 * Fails when frontend source reintroduces browser persistence for business data.
 * The migration is intentionally explicit: transient React state and API
 * services are allowed; browser storage is not a source of truth.
 */
import { readFile, readdir } from "node:fs/promises";
import path from "node:path";

const root = path.resolve("src/frontend/src");
const ignored = new Set([
  "SERVER_OWNED_DATA.md",
  "STORAGE_AUDIT_REPORT.md",
  "serverOwnedData.ts",
  "storageAdapter.ts",
]);
const forbidden = [
  /\blocalStorage\b/g,
  /\bsessionStorage\b/g,
  /\bindexedDB\b/g,
  /storage\.(getItem|setItem|removeItem|clear|key|length)\b/g,
  /medicare_(?:drug_reminders|audit_log|current_doctor|logged_in_doctor)/g,
  /(?:patients_|drugReminders_|medAdminRecord_)/g,
];

async function filesIn(directory) {
  const entries = await readdir(directory, { withFileTypes: true });
  const files = [];
  for (const entry of entries) {
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...(await filesIn(fullPath)));
    else if (/\.(?:ts|tsx|js|jsx)$/.test(entry.name)) files.push(fullPath);
  }
  return files;
}

const violations = [];
for (const file of await filesIn(root)) {
  if (ignored.has(path.basename(file))) continue;
  const source = await readFile(file, "utf8");
  for (const pattern of forbidden) {
    pattern.lastIndex = 0;
    let match;
    while ((match = pattern.exec(source))) {
      const line = source.slice(0, match.index).split("\n").length;
      violations.push(`${path.relative(process.cwd(), file)}:${line}: ${match[0]}`);
    }
  }
}

if (violations.length) {
  console.error("Server-owned data audit failed. Migrate these references to domain services:");
  for (const violation of violations) console.error(`- ${violation}`);
  process.exitCode = 1;
} else {
  console.log("Server-owned data audit passed.");
}
