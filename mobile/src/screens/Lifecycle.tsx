import React, { useState } from "react";
import { ActivityIndicator, Text, View } from "react-native";
import { apiGet, apiPost, apiUpload } from "../api/client";
import {
  Accordion,
  Button,
  Card,
  Empty,
  Icon,
  KV,
  Notice,
  Page,
  ProgressSteps,
  Pill,
  Sheet,
  s,
} from "../components/UI";
import { Field, NativeForm } from "../components/NativeForm";
import { useData } from "../components/useData";
import { colors as c, label, money, Row, rowsOf, ScreenProps } from "../theme";
import {
  FacilityHistory,
  FileList,
  noticeFields,
  PaymentSummary,
  profileFields,
} from "./Resident";
import { printFacilityForm } from "../components/Printing";
import { calendarDate } from "../theme/calendar";
const conditionOptions = {
  uninspected: "Belum diperiksa",
  good: "Baik",
  needs_repair: "Perlu perbaikan",
  damaged: "Rusak",
  missing: "Hilang",
  not_available: "Tidak tersedia",
};
export function LifecycleScreen(props: ScreenProps & { params: Row }) {
  const id = props.params.id;
  const { data, loading, error, reload } = useData<Row>(
    "admin/lifecycle/" + id,
  );
  const [form, setForm] = useState<Row | null>(null),
    [message, setMessage] = useState(""),
    [actionError, setActionError] = useState(""),
    [busy, setBusy] = useState("");
  const person = data?.case ?? {},
    checkout = data?.checkout ?? {},
    inspections = rowsOf(data?.inspections),
    items = rowsOf(data?.inventory_items);
  const role = props.session?.user?.role ?? "viewer";
  const readonly = role === "viewer",
    finance = ["owner", "super_admin", "admin", "manager", "finance"].includes(
      role,
    ),
    manage = ["owner", "super_admin", "admin", "manager"].includes(role),
    inspect = !readonly && role !== "finance";
  const endpoint = "admin/lifecycle/" + id + "/action";
  function open(
    action: string,
    title: string,
    fields: Field[],
    initial: Row = {},
  ) {
    setForm({ action, title, fields, initial });
  }
  async function run(action: string, payload: Row = {}, files: Row = {}) {
    setBusy(action);
    setMessage("");
    setActionError("");
    try {
      const result = Object.keys(files).length
        ? await apiUpload<Row>(endpoint, { action, ...payload }, files)
        : await apiPost<Row>(endpoint, { action, ...payload });
      setMessage(result?.message || "Proses berhasil disimpan.");
      await reload();
      return result;
    } finally {
      setBusy("");
    }
  }
  async function submit(values: Row, files: Row) {
    if (!form) return;
    let payload = { ...form.initial, ...values };
    if (form.action === "inspection_save") {
      const rows: Row = {};
      for (const item of items)
        rows[item.id] = {
          condition_status: payload["condition_" + item.id],
          actual_qty: payload["qty_" + item.id],
          notes: payload["notes_" + item.id],
        };
      payload = { ...payload, items: rows };
    }
    await run(form.action, payload, files);
    setForm(null);
  }
  function inspectionForm(check?: Row, revision = false) {
    const existing = check && !revision;
    const values: Row = {
      stage: check?.stage ?? "check_in",
      check_date: check?.check_date ?? calendarDate(),
      period: check?.period ?? calendarDate().slice(0, 7),
      officer_name: check?.officer_name ?? props.session?.user?.name ?? "",
      notes: revision
        ? "Revisi pemeriksaan #" + check?.id + ": "
        : (check?.notes ?? ""),
      inspection_id: existing ? check?.id : undefined,
      revision_of: revision ? check?.id : undefined,
    };
    const old = rowsOf(check?.rows);
    const fields: Field[] = [
      {
        key: "stage",
        label: "Jenis pemeriksaan",
        type: "select",
        options: existing
          ? { [check!.stage]: label(check!.stage) }
          : {
              check_in: "Tanda terima awal",
              routine: "Checklist bulanan",
              check_out: "Pemeriksaan checkout",
            },
        required: true,
      },
      {
        key: "check_date",
        label: "Tanggal pemeriksaan aktual",
        type: "date",
        required: true,
        readonly: !!existing,
      },
      {
        key: "period",
        label: "Periode pemeriksaan",
        type: "month",
        required: true,
        readonly: !!existing,
      },
      { key: "officer_name", label: "Nama petugas", required: true },
      {
        key: "scan_file",
        label: existing
          ? "Ganti scan (opsional)"
          : "Scan formulir bertanda tangan",
        type: "file",
        required: !existing,
        help: check
          ? "Periksa jumlah dan kondisi berdasarkan scan. Nilai boleh belum lengkap selama masih draf; semua fasilitas wajib diverifikasi sebelum finalisasi."
          : "Simpan scan sebagai draf, lalu pilih Baca scan dengan OCR lokal dan Koreksi draf. Jika OCR tidak tersedia, isi hasil pemeriksaan secara manual melalui Koreksi draf. Semua fasilitas tetap Belum diperiksa sampai diverifikasi.",
      },
      {
        key: "photo_file",
        label: "Foto kondisi fasilitas (opsional)",
        type: "file",
        accept: "image",
      },
    ];
    for (const item of items) {
      const r = old.find((row) => String(row.item_id) === String(item.id));
      values["condition_" + item.id] = r?.condition_status ?? "uninspected";
      values["qty_" + item.id] = r?.actual_qty ?? "";
      values["notes_" + item.id] = r?.notes ?? "";
      if (!check) continue;
      fields.push(
        {
          key: "condition_" + item.id,
          label: item.item_name + " · kondisi",
          type: "select",
          options: conditionOptions,
          required: true,
        },
        {
          key: "qty_" + item.id,
          label:
            item.item_name +
            " · jumlah aktual (standar " +
            item.standard_qty +
            ")",
          type: "number",
          required: false,
        },
        { key: "notes_" + item.id, label: item.item_name + " · catatan" },
      );
    }
    fields.push({
      key: "notes",
      label: revision
        ? "Alasan revisi dan catatan pemeriksaan"
        : "Catatan pemeriksaan",
      type: "textarea",
      required: revision,
    });
    open(
      "inspection_save",
      revision
        ? "Revisi pemeriksaan final"
        : existing
          ? "Koreksi draf pemeriksaan"
          : "Tambah pemeriksaan",
      fields,
      values,
    );
  }
  async function immediate(action: string, payload: Row = {}) {
    try {
      await run(action, payload);
    } catch (e: any) {
      setActionError(e.message || "Proses belum berhasil.");
    }
  }
  if (!data)
    return (
      <Page title="Siklus penghuni" back={props.back}>
        {error ? (
          <Notice error text={error} />
        ) : (
          <ActivityIndicator color={c.teal} />
        )}
      </Page>
    );
  const pendingRefund = Math.max(
    Number(checkout.refundable_amount || 0) - Number(checkout.paid_amount || 0),
    0,
  );
  return (
    <Page
      title={person.full_name ?? "Siklus penghuni"}
      subtitle={
        "Pendaftaran #" +
        id +
        " · " +
        (person.display_room ?? person.room_label ?? "")
      }
      back={props.back}
      refreshing={loading}
      onRefresh={reload}
    >
      {error ? <Notice error text={error} /> : null}
      {message ? <Notice text={message} /> : null}
      {actionError ? <Notice error text={actionError} /> : null}
      <Card style={{ backgroundColor: c.cream, borderColor: c.cream }}>
        <View style={[s.row, { justifyContent: "space-between" }]}>
          <Text style={s.eyebrow}>Perjalanan penghuni</Text>
          <View
            style={[s.iconButton, { backgroundColor: c.card, borderWidth: 0 }]}
          >
            <Icon name="person-outline" color={c.tealDark} />
          </View>
        </View>
        <Text style={s.h1}>{person.full_name}</Text>
        <Text style={s.body}>{person.phone}</Text>
        <Pill value={person.status} />
      </Card>
      <Card>
        <ProgressSteps
          steps={[
            { title: "Identitas", done: !!person.id },
            { title: "Bayar", done: !!person.payment_verified_at },
            { title: "Biodata", done: !!person.profile_completed_at },
            {
              title: "Tinggal",
              done: ["active", "notice", "checked_out"].includes(person.status),
            },
            { title: "Keluar", done: !!checkout.actual_date },
            {
              title: "Refund",
              done:
                !!checkout.actual_date &&
                pendingRefund === 0 &&
                Number(checkout.disputed_amount || 0) === 0,
            },
          ]}
        />
      </Card>
      <Accordion title="Identitas & pembayaran" initial>
        <KV
          title="Rencana masuk / masa tinggal"
          value={
            person.planned_start +
            " · #" +
            (person.stay_id ?? "Belum ditautkan")
          }
        />
        <PaymentSummary data={data} />
        <FileList files={data.files} />
        {manage && !person.stay_id ? (
          <>
            <Button
              title="Buat reservasi dan tagihan awal"
              onPress={() =>
                open("reserve", "Kesepakatan reservasi", [
                  {
                    key: "end_date",
                    label: "Tanggal akhir sewa",
                    type: "date",
                    required: true,
                  },
                  {
                    key: "monthly_price",
                    label: "Harga sewa bulanan (Rp)",
                    type: "number",
                    required: true,
                  },
                  {
                    key: "deposit",
                    label: "Deposit disepakati (Rp)",
                    type: "number",
                    required: true,
                  },
                ])
              }
            />
            <Button
              secondary
              title="Tautkan reservasi existing"
              onPress={() =>
                open("link_stay", "Hubungkan masa tinggal", [
                  {
                    key: "stay_id",
                    label: "Masa tinggal",
                    type: "select",
                    options: data.options?.stays ?? data.stays,
                    required: true,
                  },
                ])
              }
            />
          </>
        ) : null}
        {finance && person.stay_id && !person.payment_verified_at ? (
          <Button
            title="Verifikasi pembayaran awal"
            loading={busy === "verify_payment"}
            onPress={() =>
              open("verify_payment", "Verifikasi transaksi nyata", [
                {
                  key: "confirmed",
                  label:
                    "Saya telah memeriksa transaksi pembayaran sewa nyata yang dicatat.",
                  type: "checkbox",
                  required: true,
                },
              ])
            }
          />
        ) : null}
        {person.stay_id ? (
          <Button
            title="Buka pembayaran"
            secondary
            onPress={() =>
              props.navigate("module", {
                module: "payments",
                filters: {
                  room_id: person.room_id,
                  q: person.full_name,
                  period_id: data.payment_summary?.invoice_period_id,
                },
              })
            }
          />
        ) : null}
        {manage &&
        person.payment_verified_at &&
        person.profile_completed_at &&
        !["active", "notice", "checked_out"].includes(person.status) ? (
          <Button
            title="Aktifkan check-in"
            onPress={() =>
              open("checkin", "Serah terima dan check-in", [
                {
                  key: "contract_signed",
                  label: "Kontrak dan tata tertib telah disetujui penghuni",
                  type: "checkbox",
                  required: true,
                },
                {
                  key: "inspection_confirmed",
                  label: "Pemeriksaan awal dan fasilitas telah final",
                  type: "checkbox",
                  required: true,
                },
              ])
            }
          />
        ) : null}
      </Accordion>
      {person.profile && Object.keys(person.profile).length > 0 ? (
        <Accordion title="Biodata lengkap & kontak darurat">
          {profileFields
            .filter((field) => field.type !== "checkbox")
            .map((field) => (
              <KV
                key={field.key}
                title={field.label}
                value={label(person.profile[field.key] ?? "—")}
              />
            ))}
        </Accordion>
      ) : null}
      <Text style={s.h2}>Pemeriksaan fasilitas</Text>
      <Text style={s.muted}>
        Dokumen fisik ditandatangani bersama, lalu scan dan transkripsi
        diverifikasi. Hasil OCR hanya saran.
      </Text>
      <View style={s.row}>
        {["check_in", "routine", "check_out"].map((stage) => (
          <Button
            key={stage}
            secondary
            title={"Cetak " + label(stage)}
            onPress={async () => {
              try {
                await printFacilityForm(data, stage);
              } catch (e: any) {
                setActionError(e.message);
              }
            }}
          />
        ))}
      </View>
      {inspect && person.stay_id ? (
        <Button
          title="Tambah pemeriksaan & scan"
          icon="scan-outline"
          onPress={() => inspectionForm()}
        />
      ) : null}
      {inspections.map((check) => (
        <Accordion
          key={check.id}
          title={
            label(check.stage) +
            " · " +
            check.period +
            " · " +
            label(check.status)
          }
        >
          <KV
            title="Tanggal aktual / petugas"
            value={check.check_date + " · " + check.officer_name}
          />
          <Text style={s.body}>{check.notes}</Text>
          {rowsOf(check.rows).map((row) => (
            <View key={row.id ?? row.item_id} style={s.banner}>
              <Text style={s.h3}>{row.item_name}</Text>
              <Pill value={row.condition_status} />
              <Text style={s.body}>
                Jumlah {row.actual_qty ?? "—"} / {row.standard_qty}
              </Text>
              <Text style={s.muted}>{row.notes}</Text>
              {inspect &&
              check.status === "final" &&
              !row.maintenance_ticket_id &&
              ["damaged", "needs_repair", "missing"].includes(
                row.condition_status,
              ) ? (
                <Button
                  secondary
                  title="Buat maintenance"
                  onPress={() =>
                    open(
                      "maintenance",
                      "Tindak lanjut fasilitas",
                      [
                        {
                          key: "issue_title",
                          label: "Judul pekerjaan",
                          required: true,
                        },
                      ],
                      {
                        inspection_id: check.id,
                        item_id: row.item_id,
                        issue_title:
                          row.item_name + " · pemeriksaan #" + check.id,
                      },
                    )
                  }
                />
              ) : row.maintenance_ticket_id ? (
                <Text style={s.muted}>
                  Maintenance #{row.maintenance_ticket_id}
                </Text>
              ) : null}
            </View>
          ))}
          <FileList files={check.files} />
          {inspect && check.status === "draft" ? (
            <>
              <Button
                title="Koreksi draf"
                secondary
                onPress={() => inspectionForm(check)}
              />
              <Button
                title="Baca scan dengan OCR lokal"
                secondary
                loading={busy === "inspection_extract"}
                onPress={() =>
                  immediate("inspection_extract", { inspection_id: check.id })
                }
              />
              <Button
                title="Verifikasi & finalisasi"
                onPress={() =>
                  open(
                    "inspection_finalize",
                    "Verifikasi tanda tangan fisik",
                    [
                      {
                        key: "resident_signed",
                        label:
                          "Tanda tangan penghuni sudah diperiksa pada scan",
                        type: "checkbox",
                        required: true,
                      },
                      {
                        key: "manager_signed",
                        label:
                          "Tanda tangan pengelola sudah diperiksa pada scan",
                        type: "checkbox",
                        required: true,
                      },
                      ...(check.stage === "routine"
                        ? [
                            {
                              key: "supervisor_signed",
                              label:
                                "Tanda tangan penanggung jawab sudah diperiksa",
                              type: "checkbox",
                              required: true,
                            } as Field,
                          ]
                        : []),
                    ],
                    { inspection_id: check.id },
                  )
                }
              />
            </>
          ) : inspect ? (
            <Button
              title="Buat revisi dengan bukti baru"
              secondary
              onPress={() => inspectionForm(check, true)}
            />
          ) : null}
          {check.ocr?.ok ? (
            <View style={{ gap: 10 }}>
              <Notice
                text={
                  "Hasil " +
                  (check.ocr.engine ?? "OCR") +
                  " · perlu pemeriksaan admin, belum mengubah kondisi resmi."
                }
              />
              {rowsOf(check.ocr.items).map((r, index) => (
                <KV
                  key={index}
                  title={r.item_name}
                  value={label(r.condition_status)}
                />
              ))}
              <Text selectable style={s.muted}>
                {check.ocr.text}
              </Text>
            </View>
          ) : null}
        </Accordion>
      ))}
      {!inspections.length ? <Empty title="Belum ada pemeriksaan" /> : null}
      <Accordion title="Checkout & pengembalian deposit">
        <KV
          title="Pemberitahuan / rencana keluar"
          value={
            checkout.notice_date
              ? checkout.notice_date + " / " + checkout.planned_exit
              : "Belum diajukan"
          }
        />
        {Number(checkout.late_notice) > 0 ? (
          <Notice text="Pemberitahuan kurang dari H−15. Tidak ada potongan otomatis." />
        ) : null}
        <KV
          title="Keluar aktual / tenggat refund"
          value={
            checkout.actual_date
              ? checkout.actual_date + " / " + checkout.refund_due
              : "Menunggu checkout"
          }
        />
        {manage && !checkout.actual_date && person.stay_id ? (
          <>
            <Button
              secondary
              title={
                checkout.planned_exit
                  ? "Koreksi rencana keluar / rekening"
                  : "Catat pemberitahuan keluar"
              }
              onPress={() =>
                open(
                  checkout.planned_exit ? "notice_update" : "notice",
                  "Pemberitahuan keluar",
                  [
                    ...noticeFields,
                    ...(checkout.planned_exit
                      ? [
                          {
                            key: "revision_reason",
                            label: "Alasan koreksi",
                            type: "textarea",
                            required: true,
                          } as Field,
                        ]
                      : []),
                  ],
                  checkout,
                )
              }
            />
            <Button
              title="Selesaikan checkout"
              onPress={() =>
                open(
                  "checkout",
                  "Checkout dan perhitungan deposit",
                  [
                    {
                      key: "actual_date",
                      label: "Tanggal keluar aktual",
                      type: "date",
                      required: true,
                    },
                    {
                      key: "keys_returned",
                      label:
                        "Kunci dikembalikan, pemeriksaan checkout final dan tagihan diselesaikan",
                      type: "checkbox",
                      required: true,
                    },
                    {
                      key: "deduction_amount",
                      label: "Potongan disepakati (Rp)",
                      type: "number",
                      required: true,
                      value: 0,
                    },
                    {
                      key: "deduction_reason",
                      label: "Alasan dan referensi bukti potongan",
                      type: "textarea",
                    },
                    {
                      key: "disputed_amount",
                      label: "Nominal dalam klarifikasi (Rp)",
                      type: "number",
                      required: true,
                      value: 0,
                    },
                    {
                      key: "refund_bank",
                      label: "Bank penerima refund",
                      required: true,
                    },
                    {
                      key: "refund_account",
                      label: "Nomor rekening penerima",
                      required: true,
                    },
                  ],
                  {
                    actual_date: calendarDate(),
                    deduction_amount: "0",
                    disputed_amount: "0",
                    refund_bank: checkout.refund_bank ?? "",
                    refund_account: checkout.refund_account ?? "",
                  },
                )
              }
            />
          </>
        ) : null}
        {checkout.actual_date ? (
          <>
            <KV
              title="Deposit / potongan / klarifikasi"
              value={[
                checkout.deposit_snapshot,
                checkout.deduction_amount,
                checkout.disputed_amount,
              ]
                .map(money)
                .join(" / ")}
            />
            <KV
              title="Hak refund / sudah dibayar"
              value={
                money(checkout.refundable_amount) +
                " / " +
                money(checkout.paid_amount)
              }
            />
            <KV title="Sisa dapat dibayarkan" value={money(pendingRefund)} />
            <KV
              title="Rekening"
              value={checkout.refund_bank + " · " + checkout.refund_account}
            />
            <KV title="Rincian potongan" value={checkout.deduction_reason} />
            {finance && pendingRefund > 0 ? (
              <Button
                title="Catat transfer refund nyata"
                onPress={() =>
                  open("refund", "Pembayaran pengembalian deposit", [
                    {
                      key: "amount",
                      label: "Nominal transfer (Rp)",
                      type: "number",
                      required: true,
                    },
                    {
                      key: "transfer_date",
                      label: "Tanggal transfer aktual",
                      type: "date",
                      required: true,
                    },
                    {
                      key: "reference_no",
                      label: "Referensi transfer",
                      required: true,
                    },
                    {
                      key: "proof_file",
                      label: "Bukti transfer wajib",
                      type: "file",
                      required: true,
                    },
                  ])
                }
              />
            ) : null}
            {finance && Number(checkout.disputed_amount) > 0 ? (
              <Button
                secondary
                title="Selesaikan klarifikasi potongan"
                onPress={() =>
                  open("resolve_dispute", "Kesepakatan klarifikasi", [
                    {
                      key: "released_amount",
                      label: "Nominal dilepas untuk penghuni (Rp)",
                      type: "number",
                      required: true,
                    },
                    {
                      key: "resolution_note",
                      label: "Kesepakatan dan referensi bukti",
                      type: "textarea",
                      required: true,
                    },
                  ])
                }
              />
            ) : null}
            {pendingRefund === 0 &&
            Number(checkout.disputed_amount || 0) === 0 ? (
              <Notice text="Pengembalian deposit telah diselesaikan." />
            ) : null}
            {rowsOf(data.refunds).map((r) => (
              <Card key={r.id}>
                <KV
                  title="Transfer"
                  value={money(r.amount) + " · " + r.transfer_date}
                />
                <KV title="Referensi" value={r.reference_no} />
                <FileList
                  files={
                    r.proof_file_id
                      ? [
                          {
                            id: r.proof_file_id,
                            kind: "refund_proof",
                            original_name: "Bukti transfer refund",
                          },
                        ]
                      : []
                  }
                />
              </Card>
            ))}
          </>
        ) : null}
      </Accordion>
      <Accordion title="Riwayat administrasi">
        {rowsOf(data.history).map((event) => (
          <View key={event.id} style={s.banner}>
            <Text style={s.h3}>{label(event.event_type)}</Text>
            <Text style={s.muted}>{event.created_at}</Text>
            <Text style={s.body}>{event.description}</Text>
          </View>
        ))}
      </Accordion>
      <Sheet
        visible={!!form}
        title={form?.title ?? "Proses"}
        onClose={() => setForm(null)}
      >
        {form ? (
          <NativeForm
            key={
              form.action +
              "-" +
              (form.initial?.inspection_id ??
                form.initial?.revision_of ??
                "new")
            }
            fields={form.fields}
            initial={form.initial}
            submitLabel="Simpan proses"
            onSubmit={submit}
          />
        ) : null}
      </Sheet>
    </Page>
  );
}
