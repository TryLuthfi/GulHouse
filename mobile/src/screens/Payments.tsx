import React, { useState } from "react";
import { ActivityIndicator, Text, View } from "react-native";
import { apiGet, apiPost, apiUpload, openPrivateFile } from "../api/client";
import {
  Accordion,
  Button,
  Card,
  Empty,
  KV,
  Notice,
  Page,
  Pill,
  Sheet,
  s,
} from "../components/UI";
import { Field, NativeForm } from "../components/NativeForm";
import { useData } from "../components/useData";
import { colors as c, money, Row, rowsOf, ScreenProps } from "../theme";
import { FileList } from "./Resident";
import { printHtml } from "../components/Printing";
import { newRequestKey } from "../components/RequestKey";
import { calendarDate } from "../theme/calendar";
const paymentFields: Field[] = [
  {
    key: "payment_date",
    label: "Tanggal pembayaran",
    type: "date",
    required: true,
  },
  {
    key: "amount",
    label: "Nominal pembayaran (Rp)",
    type: "number",
    required: true,
  },
  {
    key: "payment_type",
    label: "Jenis pembayaran",
    type: "select",
    options: { rent: "Sewa", deposit: "Deposit", other: "Lainnya" },
    required: true,
  },
  { key: "method", label: "Metode pembayaran", value: "Transfer" },
  { key: "reference_no", label: "Referensi transaksi" },
  { key: "notes", label: "Catatan", type: "textarea" },
];
export function PaymentDetailScreen(props: ScreenProps & { params: Row }) {
  const id = props.params.id;
  const { data, loading, error, reload } = useData<Row>("admin/payments/" + id);
  const [form, setForm] = useState<Row | null>(null),
    [proofPayment, setProofPayment] = useState<Row | null>(null),
    [message, setMessage] = useState(""),
    [actionError, setActionError] = useState("");
  const canWrite = [
    "owner",
    "super_admin",
    "admin",
    "manager",
    "finance",
  ].includes(props.session?.user?.role ?? "viewer");
  const bill = data?.record ?? {};
  const payments = rowsOf(data?.payments);
  async function submit(v: Row) {
    if (!form) return;
    await apiPost("admin/payments/0/save-payment", {
      ...form.record,
      ...v,
      room_bill_id: id,
    });
    setForm(null);
    setMessage("Transaksi berhasil disimpan.");
    await reload();
  }
  return (
    <Page
      title="Transaksi & bukti"
      subtitle={"Tagihan #" + id}
      back={props.back}
      refreshing={loading}
      onRefresh={reload}
    >
      {error ? <Notice error text={error} /> : null}
      {message ? <Notice text={message} /> : null}
      {actionError ? <Notice error text={actionError} /> : null}
      {loading && !data ? <ActivityIndicator color={c.teal} /> : null}
      <Card style={{ backgroundColor: c.cream, borderColor: c.cream }}>
        <Text style={s.eyebrow}>Ringkasan tagihan</Text>
        <Text style={s.h1}>{bill.room_label ?? "Tagihan kamar"}</Text>
        <KV
          title="Penghuni"
          value={bill.tenant_name_snapshot ?? bill.fullname}
        />
        <KV
          title="Periode / jatuh tempo"
          value={[bill.period_label ?? bill.period_key, bill.due_date]
            .filter(Boolean)
            .join(" · ")}
        />
        <KV
          title="Tagihan / pembayaran tercatat"
          value={money(bill.base_price) + " / " + money(bill.paid_total)}
        />
        <Pill value={bill.bill_status} />
        {canWrite ? (
          <Button
            title="Tambah transaksi pembayaran"
            onPress={() =>
              setForm({
                title: "Catat pembayaran nyata",
                record: {
                  payment_id: "",
                  request_key: newRequestKey(),
                  payment_date: calendarDate(),
                  amount: "",
                  payment_type: "rent",
                  method: "Transfer",
                },
              })
            }
          />
        ) : null}
      </Card>
      {payments.map((payment) => (
        <Card key={payment.id}>
          <Text style={s.h2}>{money(payment.amount)}</Text>
          <Text style={s.body}>
            {payment.payment_date} · {payment.method}
          </Text>
          <KV
            title="Jenis / referensi"
            value={payment.payment_type + " · " + (payment.reference_no || "—")}
          />
          <KV title="Catatan" value={payment.notes} />
          <View style={s.row}>
            <Button
              secondary
              title="Bukti transfer"
              onPress={() => setProofPayment(payment)}
            />
            <Button
              secondary
              title="Cetak kuitansi"
              onPress={async () => {
                try {
                  const result = await apiGet<Row>(
                    "admin/payments/" + payment.id + "/receipt",
                  );
                  if (!result.html)
                    throw new Error("Format kuitansi belum tersedia.");
                  await printHtml(result.html);
                } catch (e: any) {
                  setActionError(e.message);
                }
              }}
            />
            {canWrite ? (
              <>
                <Button
                  secondary
                  title="Edit transaksi"
                  onPress={() =>
                    setForm({
                      title: "Koreksi pembayaran",
                      record: { ...payment, payment_id: payment.id },
                    })
                  }
                />
                <Button
                  secondary
                  title="Hapus transaksi"
                  onPress={() =>
                    setForm({
                      title: "Hapus transaksi pembayaran",
                      delete: true,
                      record: { payment_id: payment.id },
                    })
                  }
                />
              </>
            ) : null}
          </View>
        </Card>
      ))}
      {!payments.length && !loading ? (
        <Card>
          <Empty
            title="Belum ada transaksi"
            description="Bukti transfer yang dikirim penghuni tidak otomatis menjadi transaksi."
          />
        </Card>
      ) : null}
      <Sheet
        visible={!!form}
        title={form?.title ?? "Pembayaran"}
        onClose={() => setForm(null)}
      >
        {form ? (
          <NativeForm
            key={String(form.record?.payment_id) + "-" + String(form.delete)}
            fields={
              form.delete
                ? [
                    {
                      key: "confirmed",
                      label:
                        "Saya memahami penghapusan transaksi memengaruhi saldo tagihan",
                      type: "checkbox",
                      required: true,
                    },
                  ]
                : paymentFields
            }
            initial={form.record}
            submitLabel={form.delete ? "Konfirmasi hapus" : "Simpan transaksi"}
            onSubmit={async (v) => {
              if (form.delete) {
                await apiPost(
                  "admin/payments/" +
                    form.record.payment_id +
                    "/delete-payment",
                  {},
                );
                setForm(null);
                await reload();
                setMessage("Transaksi dihapus sesuai izin.");
              } else await submit(v);
            }}
          />
        ) : null}
      </Sheet>
      <Sheet
        visible={!!proofPayment}
        title={"Bukti transaksi #" + (proofPayment?.id ?? "")}
        onClose={() => setProofPayment(null)}
      >
        {proofPayment ? (
          <ProofManager
            key={proofPayment.id}
            paymentId={proofPayment.id}
            canWrite={canWrite}
          />
        ) : null}
      </Sheet>
    </Page>
  );
}
function ProofManager({
  paymentId,
  canWrite,
}: {
  paymentId: number;
  canWrite: boolean;
}) {
  const { data, error, loading, reload } = useData<Row>(
    "admin/payment-proofs/" + paymentId,
  );
  const [confirm, setConfirm] = useState<Row | null>(null);
  return (
    <View style={{ gap: 15 }}>
      {error ? <Notice error text={error} /> : null}
      {loading ? <ActivityIndicator color={c.teal} /> : null}
      <FileList kind="payment-proof" files={data?.rows} />
      {rowsOf(data?.rows).map((proof) =>
        canWrite ? (
          <View key={proof.id}>
            {confirm?.id === proof.id ? (
              <NativeForm
                fields={[
                  {
                    key: "confirmed",
                    label: "Konfirmasi penghapusan bukti ini",
                    type: "checkbox",
                    required: true,
                  },
                ]}
                submitLabel="Hapus bukti"
                onSubmit={async () => {
                  await apiPost(
                    "admin/payment-proofs/" + proof.id + "/delete",
                    {},
                  );
                  setConfirm(null);
                  await reload();
                }}
              />
            ) : (
              <Button
                title={"Hapus " + proof.original_filename}
                secondary
                onPress={() => setConfirm(proof)}
              />
            )}
          </View>
        ) : null,
      )}
      {canWrite ? (
        <NativeForm
          fields={[
            {
              key: "proof_file",
              label: "Bukti transfer",
              type: "file",
              required: true,
            },
            { key: "description", label: "Catatan bukti", type: "textarea" },
          ]}
          submitLabel="Unggah bukti"
          onSubmit={async (v, files) => {
            await apiUpload(
              "admin/payment-proofs/" + paymentId + "/upload",
              v,
              files,
            );
            await reload();
          }}
        />
      ) : null}
    </View>
  );
}
