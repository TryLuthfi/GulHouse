import { Platform } from "react-native";
import * as SecureStore from "expo-secure-store";
import * as FileSystem from "expo-file-system/legacy";
import { File as NativeFile } from "expo-file-system";
import * as Sharing from "expo-sharing";

export type PickedFile = {
  uri: string;
  name: string;
  mimeType: string;
  file?: Blob;
};
export class ApiError extends Error {
  constructor(
    message: string,
    public code = "REQUEST_FAILED",
    public status = 0,
    public fields: Record<string, string> = {},
  ) {
    super(message);
    this.name = "ApiError";
  }
}
const TOKEN_KEY = "gulhouse.mobile.token";
const BASE_KEY = "gulhouse.mobile.base";
let token: string | null = null;
let storageWrites: Promise<void> = Promise.resolve();
const expirationListeners = new Set<() => void>();
export function onSessionExpired(listener: () => void): () => void {
  expirationListeners.add(listener);
  return () => {
    expirationListeners.delete(listener);
  };
}
let base = (
  process.env.EXPO_PUBLIC_API_URL ||
  "https://gulhouse.co.id/master/index.php/api/mobile"
).replace(/\/+$/, "");
export function getApiBase() {
  return base;
}
export function setApiBase(url: string) {
  const candidate = url.trim().replace(/\/+$/, "");
  let parsed: URL;
  try {
    parsed = new URL(candidate);
  } catch {
    throw new ApiError(
      "Alamat server tidak valid. Gunakan URL API GulHouse.",
      "INVALID_SERVER",
    );
  }
  if (
    !["http:", "https:"].includes(parsed.protocol) ||
    parsed.username ||
    parsed.password ||
    parsed.search ||
    parsed.hash
  ) {
    throw new ApiError(
      "Alamat server harus berupa URL HTTP/HTTPS tanpa akun atau parameter.",
      "INVALID_SERVER",
    );
  }
  if (!/\/api\/mobile$/.test(parsed.pathname))
    throw new ApiError(
      "Alamat server harus berakhir dengan /api/mobile.",
      "INVALID_SERVER",
    );
  if (candidate !== base) token = null;
  base = candidate;
}
export function setAuthToken(value: string | null) {
  token = value;
}
export function getAuthToken() {
  return token;
}
export async function restoreSession(): Promise<{
  token: string | null;
  base: string;
}> {
  await storageWrites.catch(() => {});
  if (Platform.OS === "web") {
    try {
      const storedBase = sessionStorage.getItem(BASE_KEY);
      if (storedBase) setApiBase(storedBase);
      token = sessionStorage.getItem(TOKEN_KEY);
    } catch {
      token = null;
    }
  } else {
    const storedBase = await SecureStore.getItemAsync(BASE_KEY);
    if (storedBase) setApiBase(storedBase);
    token = await SecureStore.getItemAsync(TOKEN_KEY);
  }
  return { token, base };
}
export async function persistSession(value: string | null): Promise<void> {
  token = value;
  const savedBase = base;
  const write = async () => {
    if (Platform.OS === "web") {
      try {
        sessionStorage.removeItem(TOKEN_KEY);
        sessionStorage.setItem(BASE_KEY, savedBase);
        if (value) sessionStorage.setItem(TOKEN_KEY, value);
      } catch {
        /* Browsers may disable storage; memory session remains usable. */
      }
    } else {
      // Delete first so a partial write cannot associate an old token with a new server.
      await SecureStore.deleteItemAsync(TOKEN_KEY);
      await SecureStore.setItemAsync(BASE_KEY, savedBase);
      if (value)
        await SecureStore.setItemAsync(TOKEN_KEY, value, {
          keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
        });
    }
  };
  const next = storageWrites.catch(() => {}).then(write);
  storageWrites = next;
  await next;
}
function endpoint(resource: string) {
  if (/^[a-z]+:\/\//i.test(resource) || resource.includes(".."))
    throw new ApiError("Endpoint tidak valid.", "INVALID_ENDPOINT");
  return `${base}/${resource.replace(/^\/+/, "")}`;
}
function appendValues(
  append: (key: string, value: string) => void,
  key: string,
  value: unknown,
) {
  if (value === undefined || value === null) return;
  if (Array.isArray(value))
    value.forEach((item, i) => appendValues(append, `${key}[${i}]`, item));
  else if (typeof value === "object")
    Object.entries(value).forEach(([child, item]) =>
      appendValues(append, key ? `${key}[${child}]` : child, item),
    );
  else
    append(
      key,
      typeof value === "boolean" ? (value ? "1" : "0") : String(value),
    );
}
async function request<T>(
  resource: string,
  init: RequestInit = {},
  timeoutMs = 45000,
): Promise<T> {
  const requestToken = token;
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const response = await fetch(endpoint(resource), {
      ...init,
      credentials: "omit",
      signal: controller.signal,
      headers: {
        Accept: "application/json",
        ...(requestToken ? { Authorization: `Bearer ${requestToken}` } : {}),
        ...init.headers,
      },
    });
    const text = await response.text();
    let envelope: any;
    try {
      envelope = JSON.parse(text);
    } catch {
      throw new ApiError(
        "Server tidak mengirim respons API yang valid. Periksa alamat server.",
        "INVALID_RESPONSE",
        response.status,
      );
    }
    if (!response.ok || envelope.ok === false) {
      const publicAuth =
        /^(auth\/(admin|resident|payment)|resident\/intake|catalog\/)/.test(
          resource,
        );
      if (
        response.status === 401 &&
        !publicAuth &&
        requestToken &&
        token === requestToken
      ) {
        await persistSession(null).catch(() => {});
        if (token === null)
          expirationListeners.forEach((listener) => listener());
      }
      const error = envelope.error || {};
      throw new ApiError(
        error.message || envelope.message || "Permintaan belum berhasil.",
        error.code || "REQUEST_FAILED",
        response.status,
        error.fields || {},
      );
    }
    return (envelope.data === undefined ? envelope : envelope.data) as T;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    if (error instanceof Error && error.name === "AbortError")
      throw new ApiError(
        "Server belum merespons. Muat ulang untuk memeriksa hasil sebelum mengulangi penyimpanan.",
        "TIMEOUT",
      );
    throw new ApiError(
      "Tidak dapat terhubung ke server. Periksa koneksi dan alamat server GulHouse.",
      "NETWORK_ERROR",
    );
  } finally {
    clearTimeout(timer);
  }
}
export async function apiGet<T = any>(
  resource: string,
  params?: Record<string, unknown>,
): Promise<T> {
  const query = new URLSearchParams();
  if (params)
    Object.entries(params).forEach(([key, value]) =>
      appendValues((k, v) => query.append(k, v), key, value),
    );
  return request<T>(resource + (query.size ? `?${query}` : ""));
}
export async function apiPost<T = any>(
  resource: string,
  payload: Record<string, unknown> = {},
): Promise<T> {
  return request<T>(
    resource,
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    },
    payload.action === "inspection_extract" ? 120000 : 45000,
  );
}
export async function apiUpload<T = any>(
  resource: string,
  payload: Record<string, unknown>,
  files: Record<string, PickedFile> = {},
): Promise<T> {
  const form = new FormData();
  form.append("payload", JSON.stringify(payload));
  Object.entries(payload).forEach(([key, value]) =>
    appendValues((k, v) => form.append(k, v), key, value),
  );
  for (const [key, file] of Object.entries(files)) {
    if (Platform.OS === "web") {
      const blob = file.file || (await (await fetch(file.uri)).blob());
      form.append(key, blob, file.name);
    } else {
      // Expo's native fetch serializes byte-backed file parts, not RN's old URI objects.
      const localFile = new NativeFile(file.uri);
      form.append(key, {
        name: file.name,
        type: file.mimeType,
        bytes: () => localFile.bytes(),
      } as any);
    }
  }
  return request<T>(
    resource,
    { method: "POST", body: form },
    payload.action === "inspection_extract" ? 120000 : 45000,
  );
}
export async function openPrivateFile(
  kind: string,
  id: number | string,
  name = "gulhouse-document.pdf",
): Promise<void> {
  if (!token)
    throw new ApiError(
      "Silakan masuk untuk membuka dokumen.",
      "UNAUTHENTICATED",
      401,
    );
  const url = endpoint(
    `files/${encodeURIComponent(kind)}/${encodeURIComponent(String(id))}`,
  );
  const safeName = name.replace(/[^a-zA-Z0-9._-]/g, "_");
  const fileType = (mime: string) => mime.split(";")[0].trim().toLowerCase();
  const properName = (mime: string) => {
    const extensions: Record<string, string> = {
      "application/pdf": ".pdf",
      "image/jpeg": ".jpg",
      "image/png": ".png",
      "image/webp": ".webp",
      "text/csv": ".csv",
    };
    const extension = extensions[fileType(mime)];
    return extension
      ? safeName.replace(/\.[a-z0-9]{1,6}$/i, "") + extension
      : safeName;
  };
  if (Platform.OS === "web") {
    const response = await fetch(url, {
      headers: { Authorization: `Bearer ${token}` },
      credentials: "omit",
    });
    if (!response.ok)
      throw new ApiError(
        "Dokumen belum dapat dibuka.",
        "FILE_FAILED",
        response.status,
      );
    const blob = await response.blob(),
      objectUrl = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = objectUrl;
    link.download = properName(blob.type);
    link.click();
    setTimeout(() => URL.revokeObjectURL(objectUrl), 30000);
  } else {
    if (!FileSystem.cacheDirectory)
      throw new ApiError(
        "Penyimpanan sementara tidak tersedia.",
        "FILE_FAILED",
      );
    const local = `${FileSystem.cacheDirectory}gh-${Date.now()}-${safeName}`;
    let sharedPath = local;
    try {
      const result = await FileSystem.downloadAsync(url, local, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (result.status !== 200)
        throw new ApiError(
          "Dokumen belum dapat dibuka.",
          "FILE_FAILED",
          result.status,
        );
      if (!(await Sharing.isAvailableAsync()))
        throw new ApiError(
          "Pembuka dokumen tidak tersedia pada perangkat ini.",
          "FILE_FAILED",
        );
      const header = Object.entries(result.headers || {}).find(
        ([key]) => key.toLowerCase() === "content-type",
      );
      const mime = header ? fileType(String(header[1])) : undefined;
      if (mime === "application/json" || mime === "text/html")
        throw new ApiError(
          "Server tidak mengirim file dokumen yang valid.",
          "FILE_FAILED",
        );
      if (mime) {
        const renamed =
          local.slice(0, local.length - safeName.length) + properName(mime);
        if (renamed !== local) {
          await FileSystem.moveAsync({ from: local, to: renamed });
          sharedPath = renamed;
        }
      }
      await Sharing.shareAsync(
        sharedPath,
        mime ? { mimeType: mime } : undefined,
      );
    } finally {
      await FileSystem.deleteAsync(local, { idempotent: true }).catch(() => {});
      if (sharedPath !== local)
        await FileSystem.deleteAsync(sharedPath, { idempotent: true }).catch(
          () => {},
        );
    }
  }
}
