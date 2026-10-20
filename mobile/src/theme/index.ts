import { uiLabel } from "./copy";
export { uiLabel, uiOptions } from "./copy";
export const colors = {
  background: "#F7F5F0",
  card: "#FFFFFF",
  ink: "#2D322D",
  muted: "#5E665A",
  placeholder: "#687064",
  teal: "#6C7B57",
  tealDark: "#425438",
  soft: "#EFEEE7",
  line: "#E6E4DC",
  gold: "#A76B42",
  nav: "#2D352B",
  cream: "#EAE2D5",
  accent: "#AA7753",
  danger: "#A53E3E",
  warning: "#8B641F",
  success: "#287352",
};
export type Row = Record<string, any>;
export type Session = {
  token: string;
  role: "admin" | "resident" | "payment";
  user?: Row;
};
export type Navigate = (screen: string, params?: Row) => void;
export type ScreenProps = {
  navigate: Navigate;
  back: () => void;
  session: Session | null;
  onAuth: (session: Session) => void;
  onLogout: () => Promise<void>;
};
export const money = (value: any) =>
  "Rp " + Number(value || 0).toLocaleString("id-ID");
export const label = (value: any) =>
  (
    ({
      total: "Total data",
      properties: "Properti",
      rooms: "Total kamar",
      available: "Kamar tersedia",
      bookings: "Permintaan survey",
      monthly_potential: "Potensi sewa bulanan",
      payment_revenue: "Sewa diterima periode ini",
      payment_period: "Periode pembayaran",
      unpaid_bills: "Tagihan belum lunas",
      occupied: "Kamar terisi",
      internal: "Kamar internal",
      maintenance: "Kamar dalam perbaikan",
      room_types: "Tipe kamar",
      payment_total_all: "Akumulasi sewa diterima",
      latest_bill_total: "Total tagihan periode ini",
      latest_bill_paid: "Pembayaran tagihan periode ini",
      outstanding_amount: "Sisa tagihan",
      collection_rate: "Tingkat pembayaran",
      import_issues: "Impor perlu diperiksa",
      contracts_expiring: "Kontrak segera berakhir",
      maintenance_open: "Perbaikan berjalan",
      complaints_open: "Komplain terbuka",
      facility_open: "Fasilitas perlu ditangani",
      audit_open: "Temuan audit terbuka",
      operational_expenses: "Pengeluaran operasional",
      deposit_balance: "Saldo deposit",
      deposit_issues: "Deposit perlu diperiksa",
      latest_data_period: "Periode data terbaru",
      code: "Kode properti",
      name: "Nama",
      property_type: "Jenis properti",
      property_code: "Properti",
      type_name: "Tipe kamar",
      min_price: "Sewa terendah",
      max_price: "Sewa tertinggi",
      payment_date: "Tanggal pembayaran",
      payment_type: "Jenis pembayaran",
      period_label: "Periode",
      tenant_name_snapshot: "Nama penghuni",
      room_label: "Kamar",
      reference_no: "Referensi transaksi",
      method: "Metode pembayaran",
      notes: "Catatan",
      created_at: "Dicatat pada",
      updated_at: "Diperbarui pada",
      fullname: "Nama lengkap",
      phone: "WhatsApp",
      email: "Email",
      status: "Status",
      message: "Pesan",
      message_body: "Isi pesan",
      move_in_plan: "Rencana masuk",
      rent: "Sewa",
      deposit: "Deposit",
      other: "Lainnya",
      queued: "Dalam antrean",
      cancelled: "Dibatalkan",
      converted: "Sudah menjadi penghuni",
      assigned: "Ditugaskan",
      expiring: "Segera berakhir",
      ended: "Berakhir",
      open: "Terbuka",
      amount: "Nominal",
      total_rooms: "Total kamar",
      available_rooms: "Kamar tersedia",
      occupied_rooms: "Kamar terisi",
      active_tenants: "Penghuni aktif",
      occupancy_rate: "Tingkat hunian",
      monthly_revenue: "Pendapatan bulanan",
      paid_total: "Total pembayaran",
      unpaid_total: "Belum dibayar",
      total_billed: "Total tagihan",
      payment_count: "Jumlah transaksi",
      empty_rooms: "Kamar kosong",
      reserved_rooms: "Kamar reservasi",
      maintenance_rooms: "Dalam perbaikan",
      intake: "Identitas awal",
      reserved: "Reservasi",
      paid: "Lunas",
      active: "Aktif",
      notice: "Pemberitahuan keluar",
      checked_out: "Sudah keluar",
      good: "Baik",
      needs_repair: "Perlu perbaikan",
      damaged: "Rusak",
      missing: "Hilang",
      not_available: "Tidak tersedia",
      uninspected: "Belum diperiksa",
      draft: "Draf",
      final: "Final",
      routine: "Pemeriksaan bulanan",
      check_in: "Serah terima awal",
      check_out: "Pemeriksaan keluar",
      pending_refund: "Menunggu refund",
      partial_refund: "Refund sebagian",
      disputed: "Dalam klarifikasi",
      rent_proof: "Bukti pembayaran",
      refund_proof: "Bukti refund",
      ktp: "KTP",
      marriage: "Buku nikah",
      scan: "Scan fasilitas",
      photo: "Foto fasilitas",
      male: "Laki-laki",
      female: "Perempuan",
      done: "Selesai",
      in_progress: "Dikerjakan",
      pending: "Menunggu",
      completed: "Selesai",
    }) as Row
  )[value] || uiLabel(String(value ?? "—").replace(/_/g, " "));
export function rowsOf(value: any): Row[] {
  if (Array.isArray(value))
    return value.map((item: any) =>
      item !== null && typeof item === "object"
        ? item
        : { id: item, label: String(item) },
    );
  if (value && typeof value === "object")
    return Object.entries(value).map(([id, name]) =>
      typeof name === "object"
        ? { id, ...(name as Row) }
        : { id, label: String(name) },
    );
  return [];
}
export function optionLabel(row: Row): string {
  return String(
    row.label ??
      row.display_room ??
      row.fullname ??
      row.full_name ??
      row.name ??
      row.item_name ??
      row.title ??
      row.period_label ??
      row.room_label ??
      row.code ??
      row.id ??
      "",
  );
}
