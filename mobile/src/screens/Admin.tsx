import { useSafeAreaInsets } from "react-native-safe-area-context";
import React, { useState } from "react";
import {
  ActivityIndicator,
  Pressable,
  Text,
  TextInput,
  View,
  useWindowDimensions,
} from "react-native";
import { apiGet, apiPost, apiUpload, openPrivateFile } from "../api/client";
import {
  Accordion,
  Button,
  Card,
  Empty,
  Icon,
  KV,
  Notice,
  Page,
  RecordDetails,
  Pill,
  Sheet,
  s,
} from "../components/UI";
import { Field, NativeForm } from "../components/NativeForm";
import { useData } from "../components/useData";
import {
  AdminDataTable,
  adminCellValue,
  adminRowName,
} from "../components/AdminDataTable";
import { AdminSummary } from "../components/AdminSummary";
import { useGridWidth, gridItemWidth } from "../components/useGridWidth";
import { shareTextFile } from "../components/Exports";
import { DashboardContent, metricValue, isMoneyMetric } from "./Dashboard";
import { SecureImage } from "../components/SecureImage";
import { printHtml } from "../components/Printing";
import { rotatePhoto } from "../components/PhotoRotation";
import { newRequestKey } from "../components/RequestKey";
import {
  colors as c,
  label,
  money,
  optionLabel,
  Row,
  rowsOf,
  ScreenProps,
  uiLabel,
} from "../theme";
export function schemaFields(schema: any, options: Row = {}): Field[] {
  const entries = Array.isArray(schema)
    ? schema.map((v: Row) => [v.key ?? v.name, v])
    : Object.entries(schema ?? {});
  return entries
    .map(([key, raw]: any) => {
      const f = typeof raw === "string" ? { label: raw } : raw;
      const optionKey = (
        {
          room_id: "rooms",
          tenant_id: "tenants",
          stay_id: "stays",
          contract_id: "contracts",
          period_id: "periods",
          room_bill_id: "bills",
          bill_id: "bills",
          property_code: "properties",
          room_type: "room_types",
          property_id: "properties",
          room_type_id: "room_types",
          role: "roles",
          item_id: "inventory_items",
        } as Row
      )[key];
      return {
        key,
        label: uiLabel(f.label ?? key),
        type: f.type ?? (optionKey ? "select" : "text"),
        required: !!f.required,
        options:
          f.options ??
          (key === "property_code"
            ? rowsOf(options.properties).map((r) => ({
                ...r,
                id: r.code,
                label: [r.code, r.name].filter(Boolean).join(" · "),
              }))
            : key === "room_type"
              ? Array.from(
                  new Set(rowsOf(options.room_types).map((r) => r.name)),
                ).map((name) => ({ id: name, label: name }))
              : optionKey
                ? options[optionKey]
                : undefined),
        help: f.help ?? f.hint,
        readonly: f.readonly ?? false,
        value: f.default ?? f.value,
        accept: f.accept,
      };
    })
    .filter(
      (f: Field) =>
        f.type !== "hidden" &&
        !["file_path", "password_hash", "access_hash", "storage_name"].includes(
          f.key,
        ),
    );
}
const moduleGroups = [
  {
    title: "Hunian & penghuni",
    note: "Ruang, masa tinggal, dan pengalaman penghuni",
    keys: [
      "dashboard",
      "properties",
      "room-types",
      "room_types",
      "rooms",
      "tenants",
      "contracts",
      "booking",
      "lifecycle",
    ],
  },
  {
    title: "Pembayaran & keuangan",
    note: "Tagihan, transaksi, dan dokumen keuangan",
    keys: [
      "payments",
      "payment-proof",
      "paymentproof",
      "paymentproofs",
      "payment-proofs",
      "billing-periods",
      "billing_periods",
      "deposits",
      "expenses",
      "finance",
    ],
  },
  {
    title: "Fasilitas & layanan",
    note: "Jaga kenyamanan hunian setiap hari",
    keys: [
      "inventory",
      "maintenance",
      "housekeeping",
      "complaints",
      "facilities",
      "facility-checks",
      "facility_checks",
    ],
  },
];
export function AdminScreen(props: ScreenProps) {
  const { data, loading, error, reload } = useData<Row>("admin/modules");
  const [q, setQ] = useState("");
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const layoutWidth = width - insets.left - insets.right;
  const modules = rowsOf(data?.modules);
  const filtered = modules.filter((m) =>
    JSON.stringify([m.title, m.key, m.subtitle])
      .toLowerCase()
      .includes(q.toLowerCase()),
  );
  const grid = useGridWidth(Math.min(layoutWidth, 1200) - 44);
  const columns = grid.width >= 1000 ? 4 : grid.width >= 650 ? 3 : 2;
  const cardWidth = gridItemWidth(grid.width, columns, 14);
  const known = new Set(moduleGroups.flatMap((g) => g.keys));
  const groups = [
    ...moduleGroups.map((g) => ({
      ...g,
      rows: filtered.filter((m) => g.keys.includes(m.key)),
    })),
    {
      title: "Sistem & administrasi",
      note: "Dokumen, publikasi, dan akses tim",
      rows: filtered.filter((m) => !known.has(m.key)),
    },
  ];
  const open = (mod: Row) =>
    props.navigate("module", { module: mod.key, schema: mod });
  return (
    <Page
      title="Ruang pengelola"
      subtitle={props.session?.user?.name ?? "GulHouse"}
      refreshing={loading}
      onRefresh={reload}
      right={
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Keluar pengelola"
          style={s.iconButton}
          onPress={props.onLogout}
        >
          <Icon name="log-out-outline" />
        </Pressable>
      }
    >
      <Card style={{ backgroundColor: c.cream, borderColor: c.cream, gap: 18 }}>
        <View style={[s.row, { justifyContent: "space-between" }]}>
          <Text style={s.eyebrow}>GulHouse workspace</Text>
          <View
            style={{
              width: 45,
              height: 45,
              borderRadius: 16,
              backgroundColor: c.card,
              alignItems: "center",
              justifyContent: "center",
            }}
          >
            <Text style={s.h3}>
              {String(props.session?.user?.name ?? "GH")
                .slice(0, 2)
                .toUpperCase()}
            </Text>
          </View>
        </View>
        <Text style={s.h1}>Hunian terawat.{"\n"}Penghuni nyaman.</Text>
        <Text style={s.body}>
          Mulai dari hal yang perlu Anda kelola hari ini.
        </Text>
        <View style={s.row}>
          {["dashboard", "lifecycle", "payments"]
            .map((key) => modules.find((m) => m.key === key))
            .filter(Boolean)
            .map((mod) => (
              <Pressable
                key={mod!.key}
                accessibilityRole="button"
                accessibilityLabel={uiLabel(mod!.title ?? label(mod!.key))}
                onPress={() => open(mod!)}
                style={{
                  minHeight: 48,
                  backgroundColor: c.card,
                  paddingHorizontal: 13,
                  paddingVertical: 12,
                  borderRadius: 13,
                  flexDirection: "row",
                  alignItems: "center",
                  gap: 7,
                }}
              >
                <Icon
                  name={moduleIcon(mod!.key)}
                  size={17}
                  color={c.tealDark}
                />
                <Text style={[s.h3, { fontSize: 12 }]}>{mod!.title}</Text>
              </Pressable>
            ))}
        </View>
      </Card>
      <View style={[s.input, s.row, { backgroundColor: c.card }]}>
        <Icon name="search-outline" color={c.muted} />
        <TextInput
          placeholderTextColor={c.placeholder}
          style={{
            flex: 1,
            minHeight: 25,
            fontSize: 14,
            fontFamily: "ManropeRegular",
            color: c.ink,
          }}
          accessibilityLabel="Cari modul pengelola"
          placeholder="Cari modul…"
          value={q}
          onChangeText={setQ}
        />
      </View>
      {error ? <Notice error text={error} /> : null}
      {loading && !data ? (
        <View style={s.banner}>
          <ActivityIndicator color={c.tealDark} />
          <Text style={s.muted}>Menyiapkan ruang kerja…</Text>
        </View>
      ) : null}
      {groups
        .filter((g) => g.rows.length)
        .map((group) => (
          <View key={group.title} style={{ gap: 14 }}>
            <View style={{ gap: 3 }}>
              <Text style={s.h2}>{group.title}</Text>
              <Text style={s.muted}>{group.note}</Text>
            </View>
            <View
              onLayout={grid.onLayout}
              style={{
                alignSelf: "stretch",
                flexDirection: "row",
                flexWrap: "wrap",
                gap: 14,
              }}
            >
              {group.rows.map((mod, index) => (
                <Pressable
                  key={mod.key ?? index}
                  accessibilityRole="button"
                  accessibilityLabel={uiLabel(mod.title ?? label(mod.key))}
                  onPress={() => open(mod)}
                  style={({ pressed }) => ({
                    width: cardWidth,
                    opacity: pressed ? 0.8 : 1,
                  })}
                >
                  <Card
                    style={{
                      minHeight: 168,
                      padding: 17,
                      gap: 11,
                    }}
                  >
                    <View
                      style={[
                        s.row,
                        { justifyContent: "space-between", gap: 3 },
                      ]}
                    >
                      <View
                        style={[
                          s.iconButton,
                          {
                            backgroundColor: c.soft,
                            width: 43,
                            height: 43,
                            borderRadius: 15,
                            borderWidth: 0,
                          },
                        ]}
                      >
                        <Icon
                          name={moduleIcon(mod.key)}
                          color={c.tealDark}
                          size={21}
                        />
                      </View>
                      <Icon
                        name="arrow-forward-outline"
                        size={16}
                        color={c.muted}
                      />
                    </View>
                    <Text style={s.h3}>{mod.title ?? label(mod.key)}</Text>
                    <Text style={[s.muted, { fontSize: 11, lineHeight: 17 }]}>
                      {mod.subtitle ?? "Lihat dan kelola data"}
                    </Text>
                    {mod.readonly &&
                    !rowsOf(mod.actions).some(
                      (action) => action.method === "POST",
                    ) ? (
                      <Pill value="Hanya baca" />
                    ) : null}
                  </Card>
                </Pressable>
              ))}
            </View>
          </View>
        ))}
      {!loading && !filtered.length ? (
        <Empty
          title="Modul belum ditemukan"
          description="Coba kata lain. Modul yang tersedia mengikuti hak akses akun Anda."
        />
      ) : null}
    </Page>
  );
}
function moduleIcon(key: string) {
  return (
    (
      {
        dashboard: "grid-outline",
        rooms: "bed-outline",
        properties: "business-outline",
        tenants: "people-outline",
        payments: "wallet-outline",
        lifecycle: "git-branch-outline",
        maintenance: "construct-outline",
        documents: "document-text-outline",
        reports: "bar-chart-outline",
        photos: "images-outline",
        users: "shield-outline",
        booking: "calendar-outline",
        billing: "receipt-outline",
        deposits: "cash-outline",
      } as Row
    )[key] || "apps-outline"
  );
}
const ADMIN_PAGE_SIZE = 20;
export function AdminModuleScreen(props: ScreenProps & { params: Row }) {
  const { width: moduleWidth } = useWindowDimensions();
  const key = props.params.module;
  const [q, setQ] = useState(props.params.filters?.q ?? ""),
    [query, setQuery] = useState(props.params.filters?.q ?? ""),
    [offset, setOffset] = useState(0),
    [form, setForm] = useState<Row | null>(null),
    [success, setSuccess] = useState(""),
    [actionError, setActionError] = useState(""),
    [preview, setPreview] = useState<Row | null>(null),
    [selectedRow, setSelectedRow] = useState<Row | null>(null),
    [filters, setFilters] = useState<Row>(props.params.filters ?? {}),
    [filterOpen, setFilterOpen] = useState(false);
  const { data, loading, error, reload } = useData<Row>("admin/" + key, {
    ...filters,
    q: query,
    limit: ADMIN_PAGE_SIZE,
    offset,
  });
  const schema = data?.schema ?? props.params.schema ?? {};
  const options = data?.options ?? {};
  const rows = rowsOf(data?.rows);
  const actions = rowsOf(schema.actions);
  const readonly = !!schema.readonly;
  const saveFields = {
    ...(schema.fields ?? {}),
    ...(key === "documents"
      ? {
          document_file: { label: "Lampiran dokumen (opsional)", type: "file" },
        }
      : key === "templates"
        ? {
            template_file: {
              label: "Template PDF (opsional)",
              type: "file",
              accept: "application/pdf",
            },
          }
        : {}),
  };
  async function submit(values: Row, files: Row) {
    if (!form) return;
    const endpoint =
      form.endpoint?.replace("{id}", String(form.record?.id ?? 0)) ??
      (form.save
        ? "admin/" + key + "/save"
        : "admin/" + key + "/" + (form.record?.id ?? 0) + "/" + form.key);
    const payload = { ...(form.record ?? {}), ...values };
    const result =
      key === "photos" && form.key === "rotate"
        ? await rotatePhoto(form.record, Number(values.degrees))
        : Object.keys(files).length
          ? await apiUpload<Row>(endpoint, payload, files)
          : await apiPost<Row>(endpoint, payload);
    setSuccess(result?.message || "Proses berhasil disimpan.");
    if (result?.access_code)
      setPreview({
        title: "Kode akses penghuni · simpan sekarang",
        access_code: result.access_code,
      });
    setForm(null);
    setSelectedRow(null);
    await reload();
  }
  function openAction(action: Row, row: Row = {}) {
    if (key === "payments" && action.key === "save-payment")
      row = { ...row, request_key: row.request_key || newRequestKey() };
    if (key === "photos")
      row = {
        ...row,
        file: row.file ?? row.filename,
        property: row.property ?? "",
        type: row.type ?? "",
      };
    if (
      action.key === "detail" ||
      (key === "lifecycle" && action.key === "view")
    ) {
      props.navigate("adminLifecycle", { id: row.id });
      return;
    }
    if (action.file_kind && row[action.file_id_field ?? "id"]) {
      openPrivateFile(
        action.file_kind,
        row[action.file_id_field ?? "id"],
      ).catch((e) => setActionError(e.message));
      return;
    }
    if (action.method === "GET") {
      apiGet<Row>(
        action.endpoint?.replace("{id}", String(row.id ?? 0)) ??
          "admin/" + key + "/" + row.id + "/" + action.key,
      )
        .then((r) =>
          setPreview({ title: action.label ?? label(action.key), ...r }),
        )
        .catch((e) => setActionError(e.message));
      return;
    }
    const descriptor: Row = { ...action, record: row };
    if (key === "photos" && action.key === "rotate") {
      descriptor.fields = {
        degrees: {
          label: "Arah rotasi",
          type: "select",
          required: true,
          options: {
            "90": "90° ke kanan",
            "180": "180°",
            "270": "90° ke kiri",
          },
        },
      };
      descriptor.fileFields = [];
      setForm(descriptor);
      return;
    }
    if (action.fileFields)
      descriptor.fields = {
        ...(action.fields ?? {}),
        ...Object.fromEntries(
          rowsOf(action.fileFields).map((f: Row) => [
            f.key ?? f.id,
            {
              label:
                (
                  {
                    ktp_file: "KTP terbaru",
                    marriage_file: "Buku nikah (opsional)",
                    scan_file: "Scan bertanda tangan",
                    photo_file: "Foto fasilitas (opsional)",
                    proof_file: "Bukti transfer",
                    document_file: "Lampiran dokumen",
                    template_file: "Template PDF",
                    "photos[]": "Foto GulHouse",
                  } as Row
                )[f.key ?? f.id] ??
                f.label ??
                f.name ??
                f.id,
              type: "file",
              accept:
                (f.key ?? f.id) === "photos[]"
                  ? "image/jpeg,image/png,image/webp"
                  : (f.key ?? f.id) === "template_file"
                    ? "application/pdf"
                    : undefined,
              required:
                f.required ??
                ["ktp_file", "proof_file", "photos[]"].includes(f.key ?? f.id),
            },
          ]),
        ),
      };
    setForm(descriptor);
  }
  function renderRowActions(row: Row) {
    return (
      <View style={s.row}>
        {key === "documents" ? (
          <Button
            title="Buka lampiran"
            secondary
            onPress={() =>
              openPrivateFile("document", Number(row.id)).catch((e) =>
                setActionError(e.message),
              )
            }
          />
        ) : null}
        {["contracts", "templates"].includes(key) ? (
          <Button
            title="Lihat / cetak dokumen"
            secondary
            onPress={async () => {
              try {
                const doc = await apiGet<Row>(
                  "admin/" +
                    key +
                    "/" +
                    encodeURIComponent(row.template_key ?? row.id) +
                    "/print",
                );
                if (doc.file_kind)
                  await openPrivateFile(doc.file_kind, doc.file_id as any);
                else if (doc.html) await printHtml(doc.html);
                else setPreview({ title: "Dokumen", ...doc });
              } catch (e: any) {
                setActionError(e.message);
              }
            }}
          />
        ) : null}
        {key === "payments" ? (
          <Button
            title="Transaksi & bukti"
            onPress={() => props.navigate("paymentDetail", { id: row.id })}
          />
        ) : null}
        {key === "lifecycle" ? (
          <Button
            title="Buka siklus penghuni"
            onPress={() => props.navigate("adminLifecycle", { id: row.id })}
          />
        ) : null}
        {key !== "lifecycle" && row.lifecycle_case_id ? (
          <Button
            title="Buka siklus penghuni"
            secondary
            onPress={() =>
              props.navigate("adminLifecycle", {
                id: row.lifecycle_case_id,
              })
            }
          />
        ) : null}
        {!readonly &&
        Object.keys(schema.fields ?? {}).length &&
        !["lifecycle", "payments", "photos"].includes(key) ? (
          <Button
            title="Edit"
            secondary
            onPress={() =>
              setForm({
                save: true,
                label: "Edit " + (schema.title ?? "data"),
                record:
                  key === "tenants"
                    ? {
                        ...row,
                        stay_id: row.id,
                        emergency_name: row.contact_name,
                        emergency_relationship: row.relationship,
                      }
                    : row,
                fields: saveFields,
              })
            }
          />
        ) : null}
        {rowsOf(row.actions ?? actions)
          .filter((a) => a.scope !== "module" && !a.global && a.key !== "save")
          .map((a) => (
            <Button
              key={a.key}
              title={a.label ?? label(a.key)}
              secondary
              onPress={() => openAction(a, row)}
            />
          ))}
      </View>
    );
  }
  function renderRowDetails(row: Row) {
    const captions: Row = { ...(schema.columns ?? {}) };
    for (const [field, metadata] of Object.entries(schema.fields ?? {}) as [
      string,
      any,
    ][]) {
      if (
        metadata?.type !== "hidden" &&
        ![
          "password",
          "password_hash",
          "access_hash",
          "storage_name",
          "file_path",
          "request_key",
        ].includes(field)
      )
        captions[field] = metadata?.label ?? field;
    }
    return (
      <View style={{ gap: 14 }}>
        <View style={s.banner}>
          <Text style={s.h3}>
            {adminRowName(row, schema.title ?? label(key))}
          </Text>
          <Text style={s.muted}>
            Rincian data dan tindakan yang tersedia untuk akun Anda.
          </Text>
        </View>
        <KV title="ID data" value={row.id} />
        {key === "payments" && row.payment_id ? (
          <KV title="ID transaksi pembayaran" value={row.payment_id} />
        ) : null}
        {Object.entries(captions)
          .filter(([field]) => row[field] !== undefined)
          .map(([field, caption]) =>
            typeof row[field] === "object" && row[field] !== null ? (
              <Accordion key={field} title={String(caption)}>
                <RecordDetails value={row[field]} />
              </Accordion>
            ) : (
              <KV
                key={field}
                title={String(caption)}
                value={adminCellValue(
                  field,
                  row[field],
                  schema.fields?.[field],
                )}
              />
            ),
          )}
        <View style={s.divider} />
        <Text style={s.h3}>Tindakan</Text>
        {renderRowActions(row)}
      </View>
    );
  }
  return (
    <Page
      title={schema.title ?? label(key)}
      back={props.back}
      refreshing={loading}
      onRefresh={reload}
    >
      {error ? <Notice error text={error} /> : null}
      {success ? <Notice text={success} /> : null}
      {actionError ? <Notice error text={actionError} /> : null}
      {key === "dashboard" && data ? <DashboardContent data={data} /> : null}
      {key !== "dashboard" && data ? (
        <AdminSummary
          module={key}
          metrics={data.metrics ?? {}}
          count={typeof data.count === "number" ? data.count : undefined}
          loaded={rows.length}
        />
      ) : null}
      <View style={s.row}>
        <TextInput
          placeholderTextColor={c.placeholder}
          style={[s.input, { flex: 1, minWidth: 160 }]}
          accessibilityLabel="Cari data"
          placeholder="Cari data…"
          value={q}
          onChangeText={setQ}
          onSubmitEditing={() => {
            setQuery(q);
            setOffset(0);
          }}
        />
        <Button
          title="Cari"
          secondary
          onPress={() => {
            setQuery(q);
            setOffset(0);
          }}
        />
      </View>
      <View style={s.row}>
        <Button
          title="Filter"
          secondary
          icon="options-outline"
          onPress={() => setFilterOpen(true)}
        />
        {![
          "dashboard",
          "photos",
          "users",
          "lifecycle",
          "properties",
          "room-types",
          "rooms",
          "tenants",
          "payments",
        ].includes(key) ? (
          <Button
            title="Ekspor CSV"
            secondary
            onPress={async () => {
              try {
                const r = await apiGet<Row>("admin/" + key + "/export", {
                  ...filters,
                  q: query,
                });
                await shareTextFile(r.filename, r.content, r.mime);
              } catch (e: any) {
                setActionError(e.message);
              }
            }}
          />
        ) : null}
        {!readonly &&
        Object.keys(schema.fields ?? {}).length &&
        !["lifecycle", "payments", "photos"].includes(key) ? (
          <Button
            title="Tambah data"
            icon="add"
            onPress={() =>
              setForm({
                save: true,
                label: "Tambah " + (schema.title ?? "data"),
                record: { id: 0 },
                fields: saveFields,
              })
            }
          />
        ) : null}
        {actions
          .filter(
            (a) =>
              (a.scope === "module" || a.global) &&
              !(key === "lifecycle" && a.key === "enroll"),
          )
          .map((a) => (
            <Button
              key={a.key}
              title={a.label ?? label(a.key)}
              secondary
              onPress={() => openAction(a)}
            />
          ))}
      </View>
      {key === "lifecycle" && actions.some((a) => a.key === "enroll") ? (
        <Button
          title="Tautkan penghuni existing"
          secondary
          onPress={() =>
            setForm({
              label: "Tautkan penghuni existing",
              endpoint: "admin/lifecycle/enroll",
              fields: {
                stay_id: {
                  label: "Masa tinggal",
                  type: "select",
                  options: options.enroll_stays ?? options.stays,
                  required: true,
                },
                ktp_file: {
                  label: "KTP terbaru",
                  type: "file",
                  required: true,
                },
                marriage_file: { label: "Buku nikah (opsional)", type: "file" },
              },
            })
          }
        />
      ) : null}
      {loading && !data ? <ActivityIndicator color={c.teal} /> : null}
      {key === "photos" ? (
        rows.map((row, index) => (
          <Card
            key={[
              row.id ?? "photo",
              row.file ?? row.filename ?? "",
              index,
            ].join(":")}
          >
            {row.url ? <SecureImage uri={row.url} /> : null}
            <Text style={s.h3}>
              {adminRowName(row, schema.title ?? "Foto")}
            </Text>
            {Object.entries(schema.columns ?? {}).map(([field, caption]) => (
              <KV
                key={field}
                title={String(caption)}
                value={adminCellValue(
                  field,
                  row[field],
                  schema.fields?.[field],
                )}
              />
            ))}
            {renderRowActions(row)}
          </Card>
        ))
      ) : key !== "dashboard" && data ? (
        <AdminDataTable
          rows={rows}
          columns={schema.columns ?? {}}
          fields={schema.fields ?? {}}
          title={schema.title ?? label(key)}
          unit={key === "tenants" ? "masa tinggal" : "data"}
          total={typeof data?.count === "number" ? data.count : undefined}
          onSelect={setSelectedRow}
        />
      ) : null}
      {!loading && !rows.length && key === "photos" ? (
        <Card>
          <Empty
            title="Belum ada data sesuai filter"
            description={schema.subtitle}
          />
        </Card>
      ) : null}
      <View style={[s.row, { justifyContent: "space-between" }]}>
        <Button
          title="Sebelumnya"
          secondary
          disabled={offset === 0}
          onPress={() => setOffset(Math.max(0, offset - ADMIN_PAGE_SIZE))}
        />
        <Text style={s.muted}>
          {rows.length ? offset + 1 : 0}–
          {rows.length ? offset + rows.length : 0}
          {data?.count !== undefined ? " dari " + data.count : ""}
        </Text>
        <Button
          title="Berikutnya"
          secondary
          disabled={
            data?.count !== undefined
              ? offset + rows.length >= data.count
              : rows.length < ADMIN_PAGE_SIZE
          }
          onPress={() => setOffset(offset + ADMIN_PAGE_SIZE)}
        />
      </View>
      <Sheet
        visible={filterOpen}
        title="Filter data"
        onClose={() => setFilterOpen(false)}
      >
        <NativeForm
          key={key + "-filter"}
          fields={[
            { key: "period", label: "Periode (YYYY-MM)", type: "month" },
            ...(options.periods
              ? [
                  {
                    key: "period_id",
                    label: "Periode tagihan",
                    type: "select",
                    options: options.periods,
                  },
                ]
              : []),
            ...(schema.fields?.[schema.status_field ?? "status"]?.options
              ? [
                  {
                    key: key === "tenants" ? "stay_status" : "status",
                    label: key === "tenants" ? "Status masa tinggal" : "Status",
                    type: "select",
                    options:
                      schema.fields[schema.status_field ?? "status"].options,
                  },
                ]
              : []),
            ...["properties", "room_types", "rooms"]
              .filter((k) => options[k])
              .map((k) => ({
                key: (
                  {
                    properties: "property_id",
                    room_types: "room_type_id",
                    rooms: "room_id",
                  } as Row
                )[k],
                label: (
                  {
                    properties: "Properti",
                    room_types: "Tipe kamar",
                    rooms: "Kamar",
                  } as Row
                )[k],
                type: "select",
                options: options[k],
              })),
          ]}
          initial={filters}
          submitLabel="Terapkan filter"
          onSubmit={async (values) => {
            setFilters(values);
            setOffset(0);
            setFilterOpen(false);
          }}
        />
      </Sheet>
      <Sheet
        visible={!!form || !!preview || !!selectedRow}
        title={form?.label ?? preview?.title ?? "Rincian & tindakan"}
        onClose={() => {
          setForm(null);
          setPreview(null);
          setSelectedRow(null);
        }}
      >
        {form ? (
          <>
            <Text style={s.muted}>
              {form.description ??
                "Periksa data sebelum menyimpan. Validasi dan hak akses mengikuti sistem GulHouse."}
            </Text>
            <NativeForm
              key={String(form.record?.id ?? 0) + "-" + (form.key ?? "save")}
              fields={schemaFields(form.fields, options)}
              initial={form.record ?? {}}
              submitLabel={
                /delete|cancel|end/.test(form.key ?? "")
                  ? "Konfirmasi tindakan"
                  : "Simpan"
              }
              onSubmit={submit}
            />
          </>
        ) : preview ? (
          <RecordDetails
            value={Object.fromEntries(
              Object.entries(preview).filter(([key]) => key !== "title"),
            )}
          />
        ) : selectedRow ? (
          renderRowDetails(selectedRow)
        ) : null}
      </Sheet>
    </Page>
  );
}
