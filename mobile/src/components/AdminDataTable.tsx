import React from "react";
import { Pressable, ScrollView, Text, View } from "react-native";
import { colors as c, label, money, Row, uiLabel } from "../theme";
import { Card, Icon, s } from "./UI";

const moneyFields = new Set([
  "amount",
  "base_price",
  "monthly_price",
  "min_price",
  "max_price",
  "price_from",
  "deposit",
  "deposit_amount",
  "deposit_balance",
  "deposit_required",
  "paid_total",
  "past_due_total",
  "outstanding_amount",
  "payment_amount",
  "cost_amount",
  "refund_amount",
  "deduction_amount",
  "disputed_amount",
  "refundable_amount",
  "paid_amount",
  "purchase_price",
  "monthly_potential",
  "total_amount",
  "total_paid",
]);
export const isAdminMoney = (field: string, metadata?: Row) =>
  moneyFields.has(field) || metadata?.type === "money";
export const isAdminStatus = (field: string) =>
  field === "status" || field.endsWith("_status");

export function adminCellValue(
  field: string,
  value: any,
  metadata?: Row,
): string {
  if (value === null || value === undefined || value === "") return "—";
  if (typeof value === "object") return "Lihat rincian";
  if (
    ["checkbox", "boolean"].includes(metadata?.type) ||
    ["is_active", "is_public"].includes(field)
  )
    return [true, 1, "1"].includes(value) ? "Ya" : "Tidak";
  if (isAdminStatus(field)) return label(value);
  if (field === "role") return uiLabel(value);
  if (isAdminMoney(field, metadata) && Number.isFinite(Number(value)))
    return money(value);
  if (
    field.endsWith("_count") ||
    [
      "total_rooms",
      "available_rooms",
      "standard_qty",
      "actual_qty",
      "quantity",
      "qty",
      "capacity",
    ].includes(field)
  )
    return Number.isFinite(Number(value))
      ? Number(value).toLocaleString("id-ID")
      : String(value);
  return String(value);
}

export function adminRowName(row: Row, title: string): string {
  return String(
    row.full_name ??
      row.fullname ??
      row.tenant_name_snapshot ??
      row.name ??
      row.title ??
      row.item_name ??
      row.issue_title ??
      row.room_label ??
      row.ticket_no ??
      row.filename ??
      title + " #" + (row.id ?? "—"),
  );
}

function columnWidth(field: string, index: number): number {
  if (
    index === 0 ||
    /name|title|description|filename|address|notes|message/.test(field)
  )
    return 210;
  if (isAdminStatus(field) || isAdminMoney(field)) return 166;
  if (/date|_at$|period/.test(field)) return 168;
  return 142;
}

export function AdminDataTable({
  rows,
  columns,
  fields,
  title,
  total,
  unit = "data",
  onSelect,
}: {
  rows: Row[];
  columns: Row;
  fields: Row;
  title: string;
  total?: number;
  unit?: string;
  onSelect: (row: Row) => void;
}) {
  const entries = Object.entries(columns);
  const displayColumns = entries.length ? entries : [["id", "ID"]];
  const widths = displayColumns.map(([field], index) =>
    columnWidth(field, index),
  );
  const fullWidth = 64 + widths.reduce((sum, width) => sum + width, 0);
  return (
    <Card style={{ padding: 0, gap: 0, overflow: "hidden" }}>
      <View style={{ padding: 18, gap: 6 }}>
        <Text style={s.h3}>{uiLabel(title)}</Text>
        <Text style={s.muted}>
          {total !== undefined
            ? total.toLocaleString("id-ID") + " " + unit + " sesuai filter · "
            : ""}
          {rows.length} ditampilkan
        </Text>
        <View style={[s.row, { gap: 6 }]}>
          <Icon name="swap-horizontal-outline" size={15} color={c.muted} />
          <Text style={[s.muted, { fontSize: 11 }]}>
            Geser untuk melihat kolom lain. Buka Aksi untuk rincian lengkap.
          </Text>
        </View>
      </View>
      <ScrollView
        horizontal
        nestedScrollEnabled
        showsHorizontalScrollIndicator
        accessibilityLabel={"Tabel " + uiLabel(title)}
        style={{ maxWidth: "100%" }}
      >
        <View style={{ width: fullWidth }}>
          <View
            style={{
              flexDirection: "row",
              backgroundColor: c.soft,
              borderTopWidth: 1,
              borderBottomWidth: 1,
              borderColor: c.line,
            }}
          >
            <View
              style={{
                width: 64,
                minHeight: 48,
                justifyContent: "center",
                paddingHorizontal: 12,
              }}
            >
              <Text style={[s.h3, { fontSize: 12 }]}>Aksi</Text>
            </View>
            {displayColumns.map(([field, caption], index) => (
              <View
                key={field}
                style={{
                  width: widths[index],
                  minHeight: 48,
                  paddingHorizontal: 12,
                  justifyContent: "center",
                }}
              >
                <Text
                  numberOfLines={2}
                  style={[s.h3, { fontSize: 12, lineHeight: 18 }]}
                >
                  {uiLabel(caption)}
                </Text>
              </View>
            ))}
          </View>
          {rows.map((row, index) => (
            <View
              key={[
                row.id ?? "row",
                row.payment_id ?? "",
                row.stay_id ?? "",
                index,
              ].join(":")}
              style={{
                flexDirection: "row",
                minHeight: 56,
                backgroundColor: index % 2 ? "#FAFAF7" : c.card,
                borderBottomWidth: 1,
                borderBottomColor: c.line,
              }}
            >
              <View
                style={{
                  width: 64,
                  minHeight: 56,
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={"Aksi " + adminRowName(row, title)}
                  onPress={() => onSelect(row)}
                  style={({ pressed }) => ({
                    width: 48,
                    minHeight: 48,
                    alignItems: "center",
                    justifyContent: "center",
                    borderRadius: 13,
                    backgroundColor: pressed ? c.cream : c.soft,
                  })}
                >
                  <Icon
                    name="ellipsis-horizontal"
                    size={19}
                    color={c.tealDark}
                  />
                </Pressable>
              </View>
              {displayColumns.map(([field], column) => (
                <View
                  key={field}
                  style={{
                    width: widths[column],
                    minHeight: 56,
                    justifyContent: "center",
                    paddingHorizontal: 12,
                    paddingVertical: 10,
                  }}
                >
                  <Text
                    numberOfLines={1}
                    style={[
                      s.body,
                      {
                        fontSize: 13,
                        lineHeight: 21,
                        color: isAdminStatus(field) ? c.tealDark : c.ink,
                        fontFamily:
                          column === 0 ? "ManropeMedium" : "ManropeRegular",
                      },
                    ]}
                  >
                    {adminCellValue(field, row[field], fields[field])}
                  </Text>
                </View>
              ))}
            </View>
          ))}
        </View>
      </ScrollView>
      {!rows.length ? (
        <View style={{ padding: 22, gap: 6 }}>
          <Text style={s.h3}>Belum ada data sesuai filter</Text>
          <Text style={s.muted}>
            Coba ubah filter atau kata pencarian Anda.
          </Text>
        </View>
      ) : null}
    </Card>
  );
}
