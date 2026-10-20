import React, { useState } from "react";
import { ActivityIndicator, Pressable, Text, View } from "react-native";
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
  ProgressSteps,
  Pill,
  s,
} from "../components/UI";
import { Field, NativeForm } from "../components/NativeForm";
import { useData } from "../components/useData";
import { colors as c, label, money, Row, rowsOf, ScreenProps } from "../theme";
export const profileFields: Field[] = [
  { key: "identity_number", label: "NIK (16 digit)", required: true },
  {
    key: "address",
    label: "Alamat sesuai KTP",
    type: "textarea",
    required: true,
  },
  { key: "birth_place", label: "Tempat lahir" },
  { key: "birth_date", label: "Tanggal lahir", type: "date", required: true },
  {
    key: "gender",
    label: "Jenis kelamin",
    type: "select",
    options: { male: "Laki-laki", female: "Perempuan" },
    required: true,
  },
  { key: "religion", label: "Agama" },
  {
    key: "marital_status",
    label: "Status perkawinan",
    type: "select",
    options: { single: "Belum menikah", married: "Menikah", divorced: "Cerai" },
  },
  { key: "occupation", label: "Pekerjaan", required: true },
  { key: "company", label: "Perusahaan / kampus" },
  { key: "email", label: "Email", type: "email" },
  { key: "vehicle_type", label: "Jenis kendaraan" },
  { key: "vehicle_plate", label: "Nomor polisi kendaraan" },
  { key: "emergency_name", label: "Nama kontak darurat", required: true },
  {
    key: "emergency_phone",
    label: "Telepon darurat",
    type: "tel",
    required: true,
  },
  { key: "emergency_relation", label: "Hubungan keluarga", required: true },
  {
    key: "emergency_address",
    label: "Alamat kontak darurat",
    type: "textarea",
    required: true,
  },
  {
    key: "consent",
    label:
      "Saya menyatakan data benar dan menyetujui penggunaan data untuk administrasi serta keadaan darurat.",
    type: "checkbox",
    required: true,
  },
];
export function AccessScreen(
  props: ScreenProps & { kind: "admin" | "resident" },
) {
  const admin = props.kind === "admin";
  return (
    <Page
      title={admin ? "Masuk pengelola" : "Portal penghuni"}
      back={props.back}
    >
      <Card style={{ backgroundColor: c.cream, borderColor: c.cream }}>
        <Icon
          name={admin ? "shield-checkmark-outline" : "person-outline"}
          size={40}
          color={c.tealDark}
        />
        <Text style={s.h1}>
          {admin
            ? "Kelola GulHouse."
            : "Masa tinggal Anda,\ndalam satu tempat."}
        </Text>
        <Text style={s.body}>
          {admin
            ? "Operasional, pembayaran, pemeriksaan fasilitas dan administrasi."
            : "Biodata, tagihan, riwayat fasilitas dan pengembalian deposit."}
        </Text>
      </Card>
      <Card>
        <Text style={s.h2}>
          {admin ? "Akun pengelola" : "Kode akses pribadi"}
        </Text>
        <NativeForm
          fields={
            admin
              ? [
                  { key: "username", label: "Username", required: true },
                  {
                    key: "password",
                    label: "Password",
                    type: "password",
                    required: true,
                  },
                ]
              : [
                  {
                    key: "phone",
                    label: "Nomor WhatsApp terdaftar",
                    type: "tel",
                    required: true,
                  },
                  {
                    key: "access_code",
                    label: "Kode akses penghuni",
                    type: "password",
                    required: true,
                  },
                ]
          }
          submitLabel="Masuk"
          onSubmit={async (v) => {
            const r = await apiPost<Row>("auth/" + props.kind, v);
            if (!r.token)
              throw new Error("Server tidak mengembalikan sesi yang valid.");
            props.onAuth({ token: r.token, role: props.kind, user: r.user });
            props.navigate(admin ? "admin" : "resident");
          }}
        />
        {!admin ? (
          <>
            <Text style={s.muted}>
              Gunakan nomor WhatsApp dan kode pribadi dari pendaftaran atau pengelola. Riwayat
              tetap tersedia setelah checkout.
            </Text>
            <Button
              title="Belum terdaftar? Isi identitas awal"
              secondary
              onPress={() => props.navigate("intake")}
            />
            <Button
              title="Lihat tagihan dengan kode akses"
              secondary
              onPress={() => props.navigate("paymentLookup")}
            />
          </>
        ) : null}
      </Card>
    </Page>
  );
}
export function IntakeScreen(props: ScreenProps) {
  const { data, error, loading, reload } = useData<Row>("catalog/rooms");
  const rooms = rowsOf(
    data?.available_rooms ?? data?.intake_rooms ?? data?.rooms,
  ).filter((r) => !r.status || r.status === "available");
  const fields: Field[] = [
    { key: "full_name", label: "Nama lengkap sesuai KTP", required: true },
    { key: "phone", label: "Nomor WhatsApp", type: "tel", required: true },
    {
      key: "room_id",
      label: "Kamar diminati",
      type: "select",
      options: rooms.map((r) => ({
        ...r,
        label:
          r.display_room ??
          [r.property_code, r.room_label ?? r.room_code ?? r.name]
            .filter(Boolean)
            .join(" · "),
      })),
      required: true,
    },
    {
      key: "planned_start",
      label: "Rencana masuk",
      type: "date",
      required: true,
    },
    {
      key: "source",
      label: "Jalur survey",
      type: "select",
      options: {
        whatsapp: "WhatsApp",
        walk_in: "Datang langsung",
        website: "Website / aplikasi",
      },
      value: "whatsapp",
      required: true,
    },
    { key: "ktp_file", label: "KTP terbaru", type: "file", required: true },
    { key: "marriage_file", label: "Buku nikah (opsional)", type: "file" },
    {
      key: "consent",
      label:
        "Data benar dan saya menyetujui penggunaan dokumen untuk administrasi penyewaan.",
      type: "checkbox",
      required: true,
    },
  ];
  return (
    <Page
      title="Identitas awal"
      subtitle="Langkah 1 · calon penghuni"
      back={props.back}
      refreshing={loading}
      onRefresh={reload}
    >
      <Card>
        <Text style={s.h1}>Sudah menemukan kamar yang cocok?</Text>
        <Text style={s.body}>
          Lengkapi identitas awal setelah survey. Pengelola akan mengonfirmasi
          kamar dan pembayaran sebelum Anda mengisi biodata lengkap.
        </Text>
        {error ? <Notice error text={error} /> : null}
        <NativeForm
          fields={fields}
          submitLabel="Kirim identitas awal"
          onSubmit={async (v, files) => {
            const r = await apiUpload<Row>("resident/intake", v, files);
            if (!r.token)
              throw new Error(
                "Pendaftaran diterima tetapi sesi belum tersedia. Hubungi pengelola.",
              );
            props.onAuth({ token: r.token, role: "resident", user: r.user });
            props.navigate("resident", { access_code: r.access_code });
          }}
        />
      </Card>
    </Page>
  );
}
export function FileList({
  files,
  kind = "lifecycle",
}: {
  files: any;
  kind?: string;
}) {
  const [error, setError] = useState(""),
    [busy, setBusy] = useState<number | null>(null);
  return (
    <View style={{ gap: 10 }}>
      {error ? <Notice error text={error} /> : null}
      {rowsOf(files).map((file) => (
        <View key={file.id} style={[s.banner, { backgroundColor: c.soft }]}>
          <Text style={s.h3}>{label(file.kind ?? "Dokumen")}</Text>
          <Text style={s.muted}>
            {file.original_name ??
              file.original_filename ??
              file.name ??
              "Dokumen privat"}
          </Text>
          <Button
            secondary
            title="Buka / bagikan dokumen"
            icon="document-attach-outline"
            loading={busy === file.id}
            onPress={async () => {
              setError("");
              setBusy(file.id);
              try {
                await openPrivateFile(
                  file.file_kind ?? kind,
                  Number(file.id),
                  file.original_name ?? file.original_filename,
                );
              } catch (e: any) {
                setError(e.message || "Dokumen belum dapat dibuka.");
              } finally {
                setBusy(null);
              }
            }}
          />
        </View>
      ))}
    </View>
  );
}
export function FacilityHistory({
  inspections,
  maintenance = [],
}: {
  inspections: any;
  maintenance?: any;
}) {
  return (
    <>
      <Text style={s.h2}>Riwayat fasilitas</Text>
      <Text style={s.muted}>
        Kondisi awal, pemeriksaan bulanan, dan kondisi saat keluar. Tanggal
        temuan bukan otomatis tanggal mulai kerusakan.
      </Text>
      {rowsOf(inspections).length ? (
        rowsOf(inspections).map((check) => (
          <Accordion
            key={check.id}
            title={label(check.stage) + " · " + check.period}
          >
            <Pill value={check.status} />
            <KV title="Tanggal pemeriksaan" value={check.check_date} />
            <KV title="Petugas" value={check.officer_name} />
            <Text style={s.body}>{check.notes}</Text>
            {rowsOf(check.rows).map((item) => (
              <View key={item.id ?? item.item_id} style={s.banner}>
                <View style={[s.row, { justifyContent: "space-between" }]}>
                  <Text style={[s.h3, { flex: 1 }]}>{item.item_name}</Text>
                  <Pill
                    value={item.condition_status}
                    warning={["damaged", "needs_repair", "missing"].includes(
                      item.condition_status,
                    )}
                  />
                </View>
                <Text style={s.body}>
                  Jumlah: {item.actual_qty ?? "—"} / {item.standard_qty}
                </Text>
                {item.notes ? <Text style={s.muted}>{item.notes}</Text> : null}
                {item.maintenance_ticket_id ? (
                  <Text style={s.muted}>
                    Tindak lanjut #{item.maintenance_ticket_id}
                  </Text>
                ) : null}
              </View>
            ))}
            <FileList files={check.files} />
          </Accordion>
        ))
      ) : (
        <Card>
          <Empty
            title="Belum ada pemeriksaan final"
            description="Scan bertanda tangan akan tampil setelah diverifikasi pengelola."
          />
        </Card>
      )}
      {rowsOf(maintenance).length ? (
        <Accordion title="Tindak lanjut perbaikan">
          {rowsOf(maintenance).map((ticket) => (
            <View key={ticket.id} style={s.banner}>
              <Text style={s.h3}>{ticket.issue_title}</Text>
              <Pill value={ticket.status} />
              <KV
                title="Dilaporkan / selesai"
                value={[ticket.reported_at, ticket.finished_at]
                  .filter(Boolean)
                  .join(" / ")}
              />
              <Text style={s.muted}>{ticket.notes}</Text>
            </View>
          ))}
        </Accordion>
      ) : null}
    </>
  );
}
export function PaymentSummary({ data }: { data: Row }) {
  const p = data.payment_summary;
  if (!p)
    return (
      <Notice text="Menunggu konfirmasi pengelola mengenai kamar, harga, periode sewa dan deposit." />
    );
  return (
    <>
      <View style={[s.banner, { flexDirection: "row", gap: 16 }]}>
        <View style={{ flex: 1, gap: 6 }}>
          <Text style={s.muted}>Sewa per bulan</Text>
          <Text style={s.h3}>{money(p.monthly_price)}</Text>
        </View>
        <View style={{ flex: 1, gap: 6 }}>
          <Text style={s.muted}>Deposit disepakati</Text>
          <Text style={s.h3}>{money(p.deposit_required)}</Text>
        </View>
      </View>
      <KV
        title="Periode masa tinggal"
        value={p.stay_start + " s.d. " + (p.stay_end || "—")}
      />
      <KV
        title="Tagihan awal / jatuh tempo"
        value={["#" + p.invoice_id, p.invoice_period, p.invoice_due]
          .filter(Boolean)
          .join(" · ")}
      />
      <KV
        title="Tagihan awal / pembayaran tercatat"
        value={money(p.invoice_amount) + " / " + money(p.rent_paid)}
      />
      <KV title="Sisa sewa awal" value={money(p.rent_outstanding)} />
      <KV
        title="Saldo / kekurangan deposit"
        value={money(p.deposit_balance) + " / " + money(p.deposit_outstanding)}
      />
      <KV title="Verifikasi pengelola" value={p.verified_at || "Menunggu"} />
    </>
  );
}
export function ResidentScreen(props: ScreenProps & { params?: Row }) {
  const { data, loading, error, reload } = useData<Row>("resident/portal");
  const [notice, setNotice] = useState(""),
    [code, setCode] = useState(props.params?.access_code || "");
  async function action(action: string, values: Row, files: Row = {}) {
    await (Object.keys(files).length
      ? apiUpload("resident/action", { action, ...values }, files)
      : apiPost("resident/action", { action, ...values }));
    setNotice("Data berhasil disimpan.");
    await reload();
  }
  if (!data)
    return (
      <Page title="Portal penghuni" onRefresh={reload} refreshing={loading}>
        {error ? (
          <Notice error text={error} />
        ) : (
          <ActivityIndicator color={c.teal} />
        )}
      </Page>
    );
  const person = data.case ?? {},
    q = data.checkout ?? {},
    pay = data.payment_summary ?? {},
    profile =
      person.profile ??
      (typeof person.profile_json === "string"
        ? JSON.parse(person.profile_json || "{}")
        : {});
  const finalRefund =
    !!q.actual_date &&
    Number(q.paid_amount) >= Number(q.refundable_amount) &&
    Number(q.disputed_amount || 0) === 0;
  return (
    <Page
      title={"Halo, " + (person.full_name ?? "Penghuni")}
      subtitle={person.display_room ?? person.room_label ?? "Portal penghuni"}
      refreshing={loading}
      onRefresh={reload}
      right={
        <Pressable
          accessibilityLabel="Keluar akun"
          style={s.iconButton}
          onPress={props.onLogout}
        >
          <Icon name="log-out-outline" />
        </Pressable>
      }
    >
      {error ? <Notice error text={error} /> : null}
      {notice ? <Notice text={notice} /> : null}
      {code ? (
        <Card>
          <Text style={s.eyebrow}>Simpan sekarang · ditampilkan sekali</Text>
          <Text style={s.h2}>Kode akses pribadi</Text>
          <Text selectable style={[s.body, { fontFamily: "monospace" }]}>
            {code}
          </Text>
          <Text style={s.muted}>
            Simpan secara pribadi untuk masuk kembali. Jangan bagikan ke grup
            atau tautan.
          </Text>
          <Button
            secondary
            title="Kode sudah saya simpan"
            onPress={() => setCode("")}
          />
        </Card>
      ) : null}
      <Card style={{ backgroundColor: c.cream, borderColor: c.cream, gap: 18 }}>
        <View style={[s.row, { justifyContent: "space-between" }]}>
          <Text style={s.eyebrow}>Ruang pribadi Anda</Text>
          <View
            style={[s.iconButton, { backgroundColor: c.card, borderWidth: 0 }]}
          >
            <Icon name="home-outline" color={c.tealDark} />
          </View>
        </View>
        <Text style={s.h1}>
          {q.actual_date
            ? "Setiap tinggal,\nmenyimpan cerita."
            : "Nyaman tinggal.\nTenang setiap hari."}
        </Text>
        <View style={s.row}>
          <Icon name="location-outline" size={17} color={c.tealDark} />
          <Text style={[s.body, { flex: 1 }]}>
            {person.display_room ??
              person.room_label ??
              "Kamar sedang disiapkan"}
          </Text>
        </View>
        <View style={s.row}>
          <Pill value={person.status} />
          {finalRefund ? <Pill value="Refund selesai" /> : null}
        </View>
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
            { title: "Keluar", done: !!q.actual_date },
            { title: "Refund", done: finalRefund },
          ]}
        />
        <Text style={s.muted}>
          {q.actual_date
            ? finalRefund
              ? "Masa tinggal dan pengembalian deposit telah selesai."
              : "Checkout tercatat. Pantau rincian pengembalian deposit di bawah."
            : !person.payment_verified_at
              ? "Langkah berikutnya: konfirmasi kesepakatan dan pembayaran awal bersama pengelola."
              : !person.profile_completed_at
                ? "Langkah berikutnya: lengkapi biodata dan kontak darurat Anda."
                : "Dokumen, fasilitas, dan pembayaran masa tinggal tersimpan di sini."}
        </Text>
      </Card>
      {person.stay_id ? (
        <View style={{ flexDirection: "row", gap: 12 }}>
          <Card style={{ flex: 1, padding: 17 }}>
            <Icon name="wallet-outline" color={c.tealDark} />
            <Text style={s.muted}>Sisa sewa awal</Text>
            <Text style={s.h3}>{money(pay.rent_outstanding)}</Text>
          </Card>
          <Card style={{ flex: 1, padding: 17 }}>
            <Icon name="shield-checkmark-outline" color={c.accent} />
            <Text style={s.muted}>Saldo deposit</Text>
            <Text style={s.h3}>{money(pay.deposit_balance)}</Text>
          </Card>
        </View>
      ) : null}
      <Accordion
        title="Kesepakatan & pembayaran awal"
        initial={!person.payment_verified_at}
      >
        <PaymentSummary data={data} />
        {person.stay_id && !q.actual_date ? (
          <>
            <Notice text="Upload bukti merupakan dokumen pendukung. Pengelola tetap memeriksa transfer nyata; upload tidak otomatis melunasi tagihan." />
            <NativeForm
              fields={[
                {
                  key: "payment_file",
                  label: "Bukti pembayaran",
                  type: "file",
                  required: true,
                },
              ]}
              submitLabel="Kirim bukti pembayaran"
              onSubmit={(v, f) =>
                action("payment_proof", { invoice_id: pay.invoice_id }, f)
              }
            />
          </>
        ) : null}
        <FileList
          files={rowsOf(data.files).filter((f) => f.kind === "rent_proof")}
        />
      </Accordion>
      <Accordion
        title="Biodata & kontak darurat"
        initial={!!person.payment_verified_at && !person.profile_completed_at}
      >
        {!person.payment_verified_at ? (
          <Notice text="Formulir biodata lengkap terbuka setelah pembayaran awal diverifikasi pengelola." />
        ) : person.profile_completed_at ? (
          <>
            {profileFields
              .filter((f) => f.type !== "checkbox")
              .map((f) => (
                <KV
                  key={f.key}
                  title={f.label}
                  value={label(profile[f.key] ?? "—")}
                />
              ))}
            <Text style={s.muted}>
              Data telah diserahkan. Hubungi pengelola untuk koreksi.
            </Text>
          </>
        ) : (
          <NativeForm
            fields={profileFields}
            initial={profile}
            submitLabel="Simpan biodata lengkap"
            onSubmit={(v, f) => action("full_profile", v, f)}
          />
        )}
      </Accordion>
      {person.stay_id ? (
        <>
          <Accordion title="Tagihan & riwayat pembayaran bulanan">
            <RecurringPayments
              data={{ bills: data.bills, payments: data.payments }}
            />
          </Accordion>
          <FacilityHistory
            inspections={data.inspections}
            maintenance={data.maintenance}
          />
        </>
      ) : null}
      <Accordion
        title="Rencana keluar & pengembalian deposit"
        initial={!!q.actual_date}
      >
        <Text style={s.body}>
          Pemberitahuan paling lambat H−15. Sisa deposit dibayarkan paling
          lambat H+2 dari tanggal keluar aktual, dalam hari kalender.
        </Text>
        {q.planned_exit ? (
          <>
            <KV
              title="Pemberitahuan / rencana keluar"
              value={q.notice_date + " / " + q.planned_exit}
            />
            {Number(q.late_notice) > 0 ? (
              <Notice text="Pemberitahuan kurang dari H−15. Pengelola meninjau tanpa potongan otomatis." />
            ) : null}
            <Text style={s.muted}>
              Hubungi pengelola bila rencana keluar atau rekening perlu
              dikoreksi.
            </Text>
          </>
        ) : person.status === "active" ? (
          <NativeForm
            fields={noticeFields}
            submitLabel="Kirim pemberitahuan keluar"
            onSubmit={(v, f) => action("notice", v, f)}
          />
        ) : (
          <Text style={s.muted}>
            Pengajuan keluar tersedia setelah masa tinggal aktif.
          </Text>
        )}
        {q.actual_date ? (
          <>
            <KV
              title="Keluar aktual / batas refund H+2"
              value={q.actual_date + " / " + q.refund_due}
            />
            <KV
              title="Deposit saat checkout"
              value={money(q.deposit_snapshot)}
            />
            <KV title="Potongan disepakati" value={money(q.deduction_amount)} />
            <KV title="Dalam klarifikasi" value={money(q.disputed_amount)} />
            <KV title="Rincian potongan" value={q.deduction_reason} />
            <KV
              title="Hak refund / sudah ditransfer"
              value={money(q.refundable_amount) + " / " + money(q.paid_amount)}
            />
            <KV
              title="Rekening tujuan"
              value={q.refund_bank + " · " + q.refund_account}
            />
            <Pill value={finalRefund ? "Refund selesai" : q.status} />
            {rowsOf(data.refunds).map((r) => (
              <Card key={r.id}>
                <Text style={s.h3}>{money(r.amount)}</Text>
                <Text style={s.muted}>
                  {r.transfer_date} · {r.reference_no}
                </Text>
                {r.proof_file_id ? (
                  <Button
                    secondary
                    title="Bukti transfer"
                    onPress={() =>
                      openPrivateFile("lifecycle", r.proof_file_id)
                    }
                  />
                ) : null}
              </Card>
            ))}
          </>
        ) : null}
      </Accordion>
      {["active", "notice"].includes(person.status) ? (
        <Accordion title="Laporkan keluhan">
          <NativeForm
            fields={[
              {
                key: "complaint_title",
                label: "Judul keluhan",
                required: true,
              },
              {
                key: "complaint_detail",
                label: "Detail keluhan",
                type: "textarea",
                required: true,
              },
            ]}
            submitLabel="Kirim keluhan"
            onSubmit={async (v) => {
              await apiPost("resident/complaints", v);
              setNotice("Keluhan berhasil dikirim.");
              await reload();
            }}
          />
        </Accordion>
      ) : null}
      <Accordion title="Dokumen pribadi">
        <FileList files={data.files} />
      </Accordion>
    </Page>
  );
}
export const noticeFields: Field[] = [
  {
    key: "planned_exit",
    label: "Rencana tanggal keluar",
    type: "date",
    required: true,
  },
  { key: "reason", label: "Alasan keluar", type: "textarea", required: true },
  { key: "refund_bank", label: "Bank penerima refund", required: true },
  { key: "refund_account", label: "Nomor rekening penerima", required: true },
];
export function RecurringPayments({ data }: { data: any }) {
  const request = useData<Row>("resident/payments");
  const source = data ?? request.data;
  const rows = rowsOf(
    source?.bills ?? source?.invoices ?? source?.rows ?? source,
  );
  return (
    <View style={{ gap: 12 }}>
      {request.error && !data ? <Notice error text={request.error} /> : null}
      {rows.length ? (
        rows.map((bill, index) => (
          <Card key={bill.id ?? index}>
            <Text style={s.h3}>
              {bill.period_label ?? bill.period_key ?? "Tagihan #" + bill.id}
            </Text>
            <Pill value={bill.bill_status ?? bill.status} />
            <KV
              title="Tagihan / pembayaran"
              value={
                money(bill.base_price ?? bill.amount) +
                " / " +
                money(bill.paid_total ?? bill.paid_amount)
              }
            />
            <KV title="Jatuh tempo" value={bill.due_date} />
            {rowsOf(
              bill.payments ??
                rowsOf(source?.payments).filter(
                  (p) => String(p.room_bill_id) === String(bill.id),
                ),
            ).map((p, i) => (
              <Text key={i} style={s.muted}>
                {p.payment_date} · {money(p.amount)} · {p.reference_no ?? ""}
              </Text>
            ))}
          </Card>
        ))
      ) : (
        <Empty title="Belum ada tagihan bulanan" />
      )}
    </View>
  );
}
