# Browser persistence migration status

The server-owned data audit is now runnable with:

```bash
cd source_build/dr.armankabir-main
npm run audit:storage
```

It scans frontend TypeScript/JavaScript and fails on browser persistence or
legacy business-data keys. The policy files and the server-backed preference
adapter are excluded from the scan. Existing violations are intentionally
reported rather than hidden; each must be migrated to a domain service backed
by the PHP API before the audit can pass.
