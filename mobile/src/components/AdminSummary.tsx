import React from "react";
import { Text, View, useWindowDimensions } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { colors as c, label, Row } from "../theme";
import { Card, s } from "./UI";
import { gridItemWidth, useGridWidth } from "./useGridWidth";
import { isMoneyMetric, metricValue } from "../screens/Dashboard";

const tenantLabels: Row = {
  tenant_count: "Penghuni unik",
  active_stays: "Tinggal aktif",
  reserved_stays: "Reservasi",
  ended_stays: "Sudah keluar",
};
export function AdminSummary({
  module,
  metrics,
  count,
  loaded,
}: {
  module: string;
  metrics: Row;
  count?: number;
  loaded: number;
}) {
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const grid = useGridWidth(
    Math.min(width - insets.left - insets.right, 1200) - 44,
  );
  const source = Object.entries(metrics ?? {}).filter(
    ([key, value]) =>
      typeof value !== "object" &&
      (module !== "tenants" || key !== "stay_count"),
  );
  const entries: [string, any][] = source.length
    ? source
    : [
        ...(count !== undefined
          ? [["result_count", count] as [string, any]]
          : []),
        ["shown_count", loaded],
      ];
  const columns = grid.width >= 700 ? 4 : 2;
  return (
    <View
      onLayout={grid.onLayout}
      style={{
        alignSelf: "stretch",
        flexDirection: "row",
        flexWrap: "wrap",
        gap: 10,
      }}
    >
      {entries.map(([key, value]) => (
        <Card
          key={key}
          style={{
            width:
              grid.width < 600 && isMoneyMetric(key)
                ? Math.floor(grid.width) - 1
                : gridItemWidth(grid.width, columns, 10),
            padding: 14,
            minHeight: 80,
            gap: 7,
            borderRadius: 18,
          }}
        >
          <Text style={[s.muted, { fontSize: 12, lineHeight: 18 }]}>
            {tenantLabels[key] ??
              (key === "result_count"
                ? "Total hasil"
                : key === "shown_count"
                  ? "Ditampilkan"
                  : label(key))}
          </Text>
          <Text
            style={[
              s.h2,
              { fontSize: isMoneyMetric(key) ? 17 : 21, lineHeight: 27 },
            ]}
          >
            {key === "result_count" ||
            key === "shown_count" ||
            tenantLabels[key]
              ? Number(value).toLocaleString("id-ID")
              : metricValue(key, value)}
          </Text>
        </Card>
      ))}
    </View>
  );
}
