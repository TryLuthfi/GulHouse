import React, { useState } from "react";
import { ActivityIndicator, Text, View } from "react-native";
import { apiPost } from "../api/client";
import {
  Button,
  Card,
  Empty,
  KV,
  Notice,
  Page,
  Pill,
  s,
} from "../components/UI";
import { NativeForm } from "../components/NativeForm";
import { useData } from "../components/useData";
import { colors as c, money, Row, rowsOf, ScreenProps } from "../theme";
export function PaymentLookupScreen(props: ScreenProps) {
  return (
    <Page title="Cek pembayaran" subtitle="Penghuni existing" back={props.back}>
      <Card>
        <Text style={s.h1}>Tagihan dengan akses pribadi.</Text>
        <Text style={s.body}>
          Gunakan nomor WhatsApp terdaftar dan kode akses pribadi. Akses ini hanya
          untuk membaca tagihan serta riwayat pembayaran Anda.
        </Text>
        <NativeForm
          fields={[
            {
              key: "phone",
              label: "Nomor HP penghuni",
              type: "tel",
              required: true,
            },
            {
              key: "access_code",
              label: "Kode akses pribadi",
              type: "password",
              required: true,
            },
          ]}
          submitLabel="Cek pembayaran"
          onSubmit={async (v) => {
            const accessCode = String(v.access_code || "").trim();
            if (!/^[a-f0-9]{64}$/i.test(accessCode))
              throw new Error("Gunakan kode akses pribadi 64 karakter dari pendaftaran atau pengelola.");
            const result = await apiPost<Row>("auth/payment", { ...v, access_code: accessCode });
            if (!result.token)
              throw new Error("Sesi pembayaran belum tersedia.");
            props.onAuth({
              token: result.token,
              role: "payment",
              user: result.user,
            });
            props.navigate("paymentHistory");
          }}
        />
        <Button
          secondary
          title="Buka portal penghuni"
          onPress={() => props.navigate("residentAccess")}
        />
      </Card>
    </Page>
  );
}
export function PaymentHistoryScreen(props: ScreenProps) {
  const [period, setPeriod] = useState("");
  const { data, loading, error, reload } = useData<Row>(
    "payment/status",
    period ? { period } : undefined,
  );
  const context = data?.context ?? data?.user ?? {};
  const bills = rowsOf(
    data?.bills ??
      data?.invoices ??
      (data?.current_bill ? [data.current_bill] : []),
  );
  const history = rowsOf(
    data?.payment_history ?? data?.history ?? data?.payments,
  );
  return (
    <Page
      title="Tagihan & pembayaran"
      subtitle="Akses baca saja"
      refreshing={loading}
      onRefresh={reload}
    >
      <Notice text="Akses ini hanya membaca pembayaran. Gunakan kode portal penghuni untuk biodata, fasilitas, dokumen dan pengajuan keluar." />
      {error ? <Notice error text={error} /> : null}
      {loading && !data ? <ActivityIndicator color={c.teal} /> : null}
      <Card>
        <Text style={s.h2}>
          {context.fullname ??
            context.full_name ??
            context.name ??
            props.session?.user?.name ??
            "Penghuni"}
        </Text>
        <KV title="Kamar" value={context.display_room ?? context.room_label} />
        <NativeForm
          fields={[
            {
              key: "period",
              label: "Periode tagihan (YYYY-MM)",
              type: "month",
              required: true,
            },
          ]}
          initial={{ period }}
          submitLabel="Tampilkan periode"
          onSubmit={async (v) => setPeriod(v.period)}
        />
      </Card>
      {bills.map((bill, index) => (
        <Card key={bill.id ?? index}>
          <Text style={s.h2}>
            {bill.period_label ?? bill.period_key ?? "Tagihan"}
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
          <KV
            title="Sisa tagihan"
            value={money(
              bill.remaining_amount ??
                Math.max(
                  Number(bill.base_price || 0) - Number(bill.paid_total || 0),
                  0,
                ),
            )}
          />
          <KV title="Jatuh tempo" value={bill.due_date} />
        </Card>
      ))}
      <Text style={s.h2}>Riwayat pembayaran</Text>
      {history.length ? (
        history.map((payment, index) => (
          <Card key={payment.id ?? index}>
            <Text style={s.h2}>{money(payment.amount)}</Text>
            <Text style={s.body}>
              {payment.payment_date} · {payment.method}
            </Text>
            <KV
              title="Periode"
              value={payment.period_label ?? payment.period_key}
            />
            <KV title="Referensi" value={payment.reference_no} />
          </Card>
        ))
      ) : (
        <Card>
          <Empty title="Belum ada pembayaran tercatat" />
        </Card>
      )}
      <Button
        title="Masuk portal penghuni lengkap"
        secondary
        onPress={() => props.navigate("residentAccess")}
      />
      <Button
        title="Keluar akses pembayaran"
        secondary
        onPress={props.onLogout}
      />
    </Page>
  );
}
