/**
 * Server-owned preference boundary.
 *
 * This module deliberately has no access to localStorage, sessionStorage,
 * IndexedDB, or any other browser persistence API. Business data must never
 * pass through this module; it belongs to a domain service backed by PHP and
 * MySQL. The API enforces the per-user ownership and authorization rules.
 */
import { get, post } from "./apiClient";

const PREFERENCE_KEY_PATTERN = /^(patient_language|sidebar_collapsed|theme|ui_[a-zA-Z0-9_-]+)$/;

function assertPreferenceKey(key: string): void {
  if (!PREFERENCE_KEY_PATTERN.test(key)) {
    throw new Error(
      `Business data cannot be stored as a client preference: ${key}`,
    );
  }
}

export const storage = {
  async getItem(key: string): Promise<string | null> {
    assertPreferenceKey(key);
    try {
      const result = await get<{ value?: string | null }>(
        "/preferences/get.php",
        { key },
      );
      return result?.value ?? null;
    } catch {
      return null;
    }
  },

  async setItem(key: string, value: string): Promise<void> {
    assertPreferenceKey(key);
    await post("/preferences/set.php", { key, value });
  },

  async removeItem(key: string): Promise<void> {
    assertPreferenceKey(key);
    await post("/preferences/remove.php", { key });
  },

  async clear(): Promise<void> {
    await post("/preferences/clear.php", {});
  },

  async length(): Promise<number> {
    const result = await get<{ count?: number }>("/preferences/count.php");
    return result?.count ?? 0;
  },

  async key(index: number): Promise<string | null> {
    const result = await get<{ key?: string | null }>(
      "/preferences/key.php",
      { index },
    );
    return result?.key ?? null;
  },
};

export async function getJson<T>(key: string): Promise<T | null> {
  const raw = await storage.getItem(key);
  if (raw === null) return null;

  try {
    return JSON.parse(raw) as T;
  } catch {
    return null;
  }
}

export function setJson(key: string, data: unknown): Promise<void> {
  return storage.setItem(key, JSON.stringify(data));
}

export async function getItemOr<T>(key: string, defaultValue: T): Promise<T> {
  return (await getJson<T>(key)) ?? defaultValue;
}

export function setItemJson(key: string, data: unknown): Promise<void> {
  return setJson(key, data);
}

export function removeItemKey(key: string): Promise<void> {
  return storage.removeItem(key);
}
