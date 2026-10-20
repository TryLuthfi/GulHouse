import { useCallback, useEffect, useRef, useState } from "react";
import { apiGet } from "../api/client";
export function useData<T = any>(
  path: string,
  params?: Record<string, unknown>,
) {
  const [data, setData] = useState<T | null>(null),
    [loading, setLoading] = useState(true),
    [error, setError] = useState("");
  const sequence = useRef(0),
    mounted = useRef(true);
  const key = JSON.stringify(params || {});
  useEffect(() => {
    mounted.current = true;
    return () => {
      mounted.current = false;
      sequence.current++;
    };
  }, []);
  const reload = useCallback(async () => {
    const request = ++sequence.current;
    setLoading(true);
    setError("");
    try {
      const next = await apiGet<T>(path, JSON.parse(key));
      if (mounted.current && request === sequence.current) setData(next);
    } catch (e: any) {
      if (mounted.current && request === sequence.current)
        setError(e.message || "Tidak dapat memuat data. Periksa koneksi.");
    } finally {
      if (mounted.current && request === sequence.current) setLoading(false);
    }
  }, [path, key]);
  useEffect(() => {
    setData(null);
    reload();
    return () => {
      sequence.current++;
    };
  }, [reload]);
  return { data, loading, error, reload, setData };
}
