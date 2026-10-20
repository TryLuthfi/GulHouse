import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useGridWidth, gridItemWidth } from "../components/useGridWidth";
import React from "react";
import { Text, View, useWindowDimensions } from "react-native";
import { Accordion, Card, Icon, KV, s } from "../components/UI";
import { colors as c, label, money, Row, rowsOf } from "../theme";
const moneyMetrics = new Set([
  "monthly_potential",
  "payment_revenue",
  "payment_total_all",
  "latest_bill_total",
  "latest_bill_paid",
  "outstanding_amount",
  "operational_expenses",
  "deposit_balance",
  "amount",
  "min_price",
  "max_price",
  "monthly_price",
  "base_price",
  "paid_total",
  "past_due_total",
  "cost_amount",
]);
const countMetrics = new Set([
  "properties",
  "rooms",
  "available",
  "bookings",
  "payment_count",
  "unpaid_bills",
  "occupied",
  "reserved",
  "internal",
  "maintenance",
  "active_tenants",
  "room_types",
  "import_issues",
  "contracts_expiring",
  "maintenance_open",
  "complaints_open",
  "facility_open",
  "audit_open",
  "deposit_issues",
  "total_rooms",
  "available_rooms",
  "bills",
]);
export const isMoneyMetric = (key: string) => moneyMetrics.has(key);
export function metricValue(key: string, value: any) {
  if (value === null || value === undefined || value === "") return "—";
  const numeric =
    typeof value === "number" || /^-?\d+(\.\d+)?$/.test(String(value));
  if (!numeric) return String(value);
  if (key === "occupancy_rate" || key === "collection_rate") {
    return Number(value).toLocaleString("id-ID") + "%";
  }
  // Counts such as unpaid_bills/payment_count must never be formatted as rupiah.
  if (moneyMetrics.has(key)) {
    return money(value);
  }
  return countMetrics.has(key)
    ? Number(value).toLocaleString("id-ID")
    : String(value);
}
export function DashboardContent({ data }: { data: Row }) {
  const summary = data.summary ?? {};
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const layoutWidth = width - insets.left - insets.right;
  const grid = useGridWidth(Math.min(layoutWidth, 1200) - 44);
  const columns = grid.width >= 850 ? 3 : 2;
  const cardWidth = gridItemWidth(grid.width, columns, 12);
  const primary = [
    "rooms",
    "occupancy_rate",
    "payment_revenue",
    "outstanding_amount",
    "monthly_potential",
    "deposit_balance",
  ].filter((k) => summary[k] !== undefined);
  const icons: Row = {
    rooms: "bed-outline",
    occupancy_rate: "pie-chart-outline",
    payment_revenue: "wallet-outline",
    outstanding_amount: "receipt-outline",
    monthly_potential: "trending-up-outline",
    deposit_balance: "shield-checkmark-outline",
  };
  const rest = Object.entries(summary).filter(
    ([k, v]) => !primary.includes(k) && typeof v !== "object",
  );
  const format = (key: string, value: any) =>
    key === "collection_rate" && Number(summary.latest_bill_total) === 0
      ? "—"
      : metricValue(key, value);
  return (
    <>
      <View style={{ gap: 8 }}>
        <Text style={s.eyebrow}>
          Periode {data.selected_period ?? "berjalan"}
        </Text>
        <Text style={s.h1}>Gambaran hunian,{"\n"}dalam satu pandangan.</Text>
        <Text style={s.muted}>
          Ringkasan kamar, pembayaran, dan layanan GulHouse.
        </Text>
      </View>
      <View
        onLayout={grid.onLayout}
        style={{
          alignSelf: "stretch",
          flexDirection: "row",
          flexWrap: "wrap",
          gap: 12,
        }}
      >
        {primary.map((key) => (
          <Card
            key={key}
            style={{
              width:
                grid.width < 600 && moneyMetrics.has(key)
                  ? Math.floor(grid.width) - 1
                  : cardWidth,
              padding: 18,
              gap: 16,
              backgroundColor: key === "payment_revenue" ? c.cream : c.card,
              borderColor: key === "payment_revenue" ? c.cream : c.line,
            }}
          >
            <View
              style={{
                width: 39,
                height: 39,
                borderRadius: 13,
                backgroundColor: key === "payment_revenue" ? c.card : c.soft,
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <Icon name={icons[key]} size={20} color={c.tealDark} />
            </View>
            <Text style={[s.muted, { minHeight: 40 }]}>{label(key)}</Text>
            <Text
              style={[
                s.h2,
                { fontSize: moneyMetrics.has(key) ? 18 : 22, lineHeight: 28 },
              ]}
            >
              {format(key, summary[key])}
            </Text>
          </Card>
        ))}
      </View>
      {summary.collection_rate !== undefined ? (
        <Card>
          <View style={[s.row, { justifyContent: "space-between" }]}>
            <View style={{ flex: 1 }}>
              <Text style={s.eyebrow}>Pembayaran periode ini</Text>
              <Text style={s.h2}>Tagihan yang terselesaikan</Text>
            </View>
            <Text style={s.h2}>
              {format("collection_rate", summary.collection_rate)}
            </Text>
          </View>
          <View
            style={{
              height: 8,
              backgroundColor: c.soft,
              borderRadius: 8,
              overflow: "hidden",
            }}
          >
            <View
              style={{
                height: 8,
                width: (Math.min(
                  100,
                  Math.max(0, Number(summary.collection_rate) || 0),
                ) + "%") as any,
                backgroundColor: c.tealDark,
                borderRadius: 8,
              }}
            />
          </View>
          <Text style={s.muted}>
            {money(summary.latest_bill_paid)} diterima dari{" "}
            {money(summary.latest_bill_total)} tagihan.
          </Text>
        </Card>
      ) : null}
      {rest.length ? (
        <Accordion title="Ringkasan operasional lainnya">
          <View style={{ flexDirection: "row", flexWrap: "wrap", gap: 14 }}>
            {rest.map(([key, value]) => (
              <View
                key={key}
                style={{
                  flexGrow: 1,
                  flexBasis: 180,
                  padding: 14,
                  borderRadius: 15,
                  backgroundColor: c.background,
                  gap: 6,
                }}
              >
                <Text style={s.muted}>{label(key)}</Text>
                <Text style={s.h3}>{format(key, value)}</Text>
              </View>
            ))}
          </View>
        </Accordion>
      ) : null}
      {[
        { key: "property_rows", title: "Ringkasan properti" },
        { key: "room_type_rows", title: "Ringkasan tipe kamar" },
        { key: "booking_rows", title: "Survey terbaru" },
        { key: "payment_rows", title: "Pembayaran terbaru" },
      ].map((group) =>
        rowsOf(data[group.key]).length ? (
          <Accordion
            key={group.key}
            title={group.title}
            initial={group.key === "property_rows"}
          >
            {rowsOf(data[group.key]).map((row, index) => (
              <View
                key={row.id ?? index}
                style={{
                  padding: 18,
                  borderRadius: 18,
                  backgroundColor: c.background,
                  gap: 4,
                }}
              >
                {Object.entries(row)
                  .filter(
                    ([key, v]) =>
                      key !== "id" &&
                      !key.endsWith("_id") &&
                      typeof v !== "object",
                  )
                  .map(([key, value]) => (
                    <KV
                      key={key}
                      title={label(key)}
                      value={metricValue(key, value)}
                    />
                  ))}
              </View>
            ))}
          </Accordion>
        ) : null,
      )}
    </>
  );
}
