import { useCallback, useState } from "react";
import { LayoutChangeEvent } from "react-native";

// Screen width is only a first-render estimate. Yoga's actual content width
// accounts for the ScrollView, safe areas, padding and native pixel rounding.
export function useGridWidth(fallback: number) {
  const [width, setWidth] = useState(Math.max(0, fallback));
  const onLayout = useCallback((event: LayoutChangeEvent) => {
    const next = event.nativeEvent.layout.width;
    if (next > 0)
      setWidth((current) => (Math.abs(current - next) > 0.5 ? next : current));
  }, []);
  return { width, onLayout };
}

export function gridItemWidth(width: number, columns: number, gap: number) {
  return Math.max(0, Math.floor((width - (columns - 1) * gap - 1) / columns));
}
