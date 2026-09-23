// Migration & sync compatibility hook — intentionally server-owned.
// There is no client migration, offline queue, or browser data snapshot.

import { useCallback, useState } from "react";

export type MigrationStatus = "idle" | "running" | "complete" | "failed";

export interface MigrationProgress {
  total: number;
  migrated: number;
  message: string;
}

export interface MigrationState {
  migrationStatus: MigrationStatus;
  migrationProgress: MigrationProgress;
  runManualMigration: () => void;
}

const COMPLETE_PROGRESS: MigrationProgress = {
  total: 1,
  migrated: 1,
  message: "All data is stored server-side via the PHP API",
};

export function useMigration(
  _serverContext?: unknown,
  _invalidateAll?: () => void,
): MigrationState {
  const [status] = useState<MigrationStatus>("complete");
  const [progress] = useState<MigrationProgress>(COMPLETE_PROGRESS);
  const runManualMigration = useCallback(() => undefined, []);

  return {
    migrationStatus: status,
    migrationProgress: progress,
    runManualMigration,
  };
}

export interface SyncStatus {
  isOnline: boolean;
  pendingChanges: number;
  lastSyncAt?: Date;
}

/**
 * Reports connectivity only. It never represents locally queued business data.
 */
export function useSyncStatus(): SyncStatus {
  const [isOnline, setIsOnline] = useState(
    typeof navigator === "undefined" ? true : navigator.onLine,
  );

  React.useEffect(() => {
    const online = () => setIsOnline(true);
    const offline = () => setIsOnline(false);
    window.addEventListener("online", online);
    window.addEventListener("offline", offline);
    return () => {
      window.removeEventListener("online", online);
      window.removeEventListener("offline", offline);
    };
  }, []);

  return { isOnline, pendingChanges: 0 };
}
