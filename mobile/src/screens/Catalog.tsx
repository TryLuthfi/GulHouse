import { useSafeAreaInsets } from "react-native-safe-area-context";
import React, { useEffect, useState } from "react";
import {
  ActivityIndicator,
  Image,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  useWindowDimensions,
} from "react-native";
import { apiPost } from "../api/client";
import {
  Button,
  Card,
  Empty,
  Icon,
  KV,
  Notice,
  Page,
  Pill,
  s,
} from "../components/UI";
import { NativeForm, Field } from "../components/NativeForm";
import { useData } from "../components/useData";
import { useGridWidth, gridItemWidth } from "../components/useGridWidth";
import { PublicInfo } from "../components/PublicInfo";
import { colors as c, money, Row, ScreenProps, rowsOf } from "../theme";
function photoOf(room: Row) {
  return (
    room.cover_image ||
    room.image ||
    room.cover ||
    room.cover_url ||
    room.photo_url
  );
}
function RoomPhoto({ room, height = 220 }: { room: Row; height?: number }) {
  const [failed, setFailed] = useState(false);
  const uri = photoOf(room);
  useEffect(() => setFailed(false), [uri]);
  return uri && !failed ? (
    <Image
      source={{ uri }}
      accessibilityLabel={
        "Foto " + (room.name ?? room.room_label ?? "kamar GulHouse")
      }
      resizeMode="cover"
      onError={() => setFailed(true)}
      style={{ width: "100%", height, backgroundColor: c.soft }}
    />
  ) : (
    <View
      style={{
        height,
        backgroundColor: c.cream,
        alignItems: "center",
        justifyContent: "center",
        gap: 12,
      }}
    >
      <Icon name="bed-outline" size={40} color={c.tealDark} />
      <Text style={s.muted}>Foto kamar belum tersedia</Text>
    </View>
  );
}
function SectionTitle({ title, note }: { title: string; note?: string }) {
  return (
    <View style={s.sectionHeading}>
      <Text style={[s.h2, { flex: 1 }]}>{title}</Text>
      {note ? <Text style={s.muted}>{note}</Text> : null}
    </View>
  );
}
function PropertyCard({
  room,
  onPress,
  compact = false,
}: {
  room: Row;
  onPress: () => void;
  compact?: boolean;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={"Detail " + (room.name ?? room.room_label)}
      onPress={onPress}
      style={({ pressed }) => ({ opacity: pressed ? 0.85 : 1 })}
    >
      <Card style={{ padding: 0, overflow: "hidden", gap: 0 }}>
        <View>
          <RoomPhoto room={room} height={compact ? 170 : 240} />
          <View style={{ position: "absolute", top: 14, left: 14 }}>
            <Pill value={room.property_code ?? room.code ?? "GulHouse"} />
          </View>
        </View>
        <View style={{ padding: 18, gap: 9 }}>
          <Text
            numberOfLines={2}
            style={[
              s.h3,
              { fontSize: compact ? 16 : 19, lineHeight: compact ? 23 : 27 },
            ]}
          >
            {room.name ?? room.room_label ?? room.type_name}
          </Text>
          <View style={[s.row, { gap: 5 }]}>
            <Icon name="location-outline" size={14} color={c.muted} />
            <Text style={[s.muted, { flex: 1 }]}>
              {room.property_name ??
                room.property_code ??
                room.code ??
                "GulHouse"}
            </Text>
          </View>
          <View
            style={[
              s.row,
              {
                justifyContent: "space-between",
                alignItems: "flex-end",
                gap: 5,
              },
            ]}
          >
            <View style={{ flex: 1 }}>
              <Text style={[s.h3, { color: c.tealDark }]}>
                {room.price_label ?? money(room.price ?? room.monthly_price)}
              </Text>
              <Text style={s.muted}>per bulan</Text>
            </View>
            <View
              style={{
                width: 38,
                height: 38,
                borderRadius: 13,
                backgroundColor: c.soft,
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <Icon name="arrow-forward-outline" size={20} color={c.tealDark} />
            </View>
          </View>
          <Text style={[s.muted, { fontSize: 11 }]}>
            {room.available_count !== undefined
              ? room.available_count + " kamar tersedia"
              : "Lihat ketersediaan kamar"}
          </Text>
        </View>
      </Card>
    </Pressable>
  );
}
export function CatalogScreen(props: ScreenProps) {
  const { data, loading, error, reload } = useData<Row>("catalog/rooms");
  const [q, setQ] = useState(""),
    [filter, setFilter] = useState("Semua");
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const layoutWidth = width - insets.left - insets.right;
  const rooms = rowsOf(
    data?.rooms ?? data?.cards ?? data?.featured_rooms ?? data,
  );
  const property = (r: Row) =>
    String(r.property_code ?? r.code ?? r.property_name ?? "GulHouse");
  const types = ["Semua", ...Array.from(new Set(rooms.map(property)))];
  const filtered = rooms.filter(
    (r) =>
      (filter === "Semua" || property(r) === filter) &&
      JSON.stringify([r.name, r.type_name, r.room_label, r.code, r.description])
        .toLowerCase()
        .includes(q.toLowerCase()),
  );
  const hero = rooms.find((r) => photoOf(r));
  const grid = useGridWidth(Math.min(layoutWidth, 1200) - 44);
  const columns = grid.width >= 960 ? 3 : grid.width >= 600 ? 2 : 1;
  const cardWidth = gridItemWidth(grid.width, columns, 18);
  const openRoom = (room: Row) =>
    props.navigate("room", { slug: room.slug ?? room.id, room });
  return (
    <Page
      title="GulHouse Living"
      subtitle="Hunian untuk keseharian Anda"
      refreshing={loading}
      onRefresh={reload}
      right={
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Pengaturan koneksi"
          style={s.iconButton}
          onPress={() => props.navigate("settings")}
        >
          <Icon name="options-outline" />
        </Pressable>
      }
    >
      <View style={{ gap: 8 }}>
        <Text style={s.eyebrow}>Selamat datang di GulHouse</Text>
        <Text style={s.display}>Ruang nyaman.{"\n"}Cerita baru.</Text>
        <Text style={s.muted}>
          Temukan hunian untuk keseharian yang lebih tenang.
        </Text>
      </View>
      <View style={[s.input, s.row, { backgroundColor: c.card, gap: 10 }]}>
        <Icon name="search-outline" size={21} color={c.muted} />
        <TextInput
          placeholderTextColor={c.placeholder}
          style={{
            flex: 1,
            minWidth: 0,
            fontFamily: "ManropeRegular",
            fontSize: 14,
            color: c.ink,
            minHeight: 26,
          }}
          accessibilityLabel="Cari kamar"
          placeholder="Cari tipe, properti, atau kamar…"
          value={q}
          onChangeText={setQ}
        />
        {q ? (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Hapus pencarian"
            onPress={() => setQ("")}
            style={{
              minWidth: 48,
              minHeight: 48,
              alignItems: "center",
              justifyContent: "center",
            }}
          >
            <Icon name="close-circle-outline" />
          </Pressable>
        ) : null}
      </View>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={{ gap: 9, paddingVertical: 2 }}
      >
        {types.map((t) => (
          <Pressable
            key={t}
            accessibilityRole="button"
            accessibilityState={{ selected: filter === t }}
            onPress={() => setFilter(t)}
            style={{
              minHeight: 48,
              paddingHorizontal: 20,
              borderRadius: 14,
              justifyContent: "center",
              borderWidth: 1,
              borderColor: filter === t ? c.tealDark : c.line,
              backgroundColor: filter === t ? c.tealDark : c.card,
            }}
          >
            <Text
              style={[
                s.h3,
                { fontSize: 13, color: filter === t ? "white" : c.ink },
              ]}
            >
              {t}
            </Text>
          </Pressable>
        ))}
      </ScrollView>
      {error ? <Notice error text={error} /> : null}
      {loading && !data ? (
        <View
          style={[
            s.banner,
            { minHeight: 220, alignItems: "center", justifyContent: "center" },
          ]}
        >
          <ActivityIndicator color={c.tealDark} />
          <Text style={s.muted}>Menyiapkan pilihan kamar…</Text>
        </View>
      ) : null}
      {!q && filter === "Semua" && hero ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={"Jelajahi " + hero.name}
          onPress={() => openRoom(hero)}
        >
          <View style={{ borderRadius: 26, overflow: "hidden" }}>
            <RoomPhoto room={hero} height={width >= 660 ? 350 : 270} />
            <View
              style={{
                position: "absolute",
                top: 18,
                left: 18,
                backgroundColor: c.card,
                paddingHorizontal: 12,
                paddingVertical: 8,
                borderRadius: 10,
              }}
            >
              <Text style={s.eyebrow}>Temukan tempat Anda</Text>
            </View>
            <View
              style={{
                position: "absolute",
                left: 0,
                right: 0,
                bottom: 0,
                padding: 20,
                backgroundColor: "#20271EDB",
                gap: 6,
              }}
            >
              <Text style={[s.h2, { color: "white" }]}>
                {hero.name ?? hero.type_name}
              </Text>
              <View style={[s.row, { justifyContent: "space-between" }]}>
                <Text style={[s.body, { color: "#F7F5F0", flex: 1 }]}>
                  {hero.property_name ?? hero.property_code ?? hero.code}
                </Text>
                <Icon name="arrow-forward" color="white" />
              </View>
            </View>
          </View>
        </Pressable>
      ) : null}
      {!q && filter === "Semua" && rooms.length > 1 ? (
        <>
          <SectionTitle
            title="Kenali pilihan hunian"
            note="Geser untuk melihat"
          />
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ gap: 16, paddingBottom: 3 }}
          >
            {rooms.slice(0, 6).map((room, i) => (
              <View key={room.slug ?? i} style={{ width: 245 }}>
                <PropertyCard
                  room={room}
                  compact
                  onPress={() => openRoom(room)}
                />
              </View>
            ))}
          </ScrollView>
        </>
      ) : null}
      <SectionTitle
        title={q || filter !== "Semua" ? "Hasil pencarian" : "Pilihan kamar"}
        note={filtered.length + " pilihan"}
      />
      <View
        onLayout={grid.onLayout}
        style={{
          alignSelf: "stretch",
          flexDirection: "row",
          flexWrap: "wrap",
          gap: 18,
        }}
      >
        {filtered.map((room, i) => (
          <View key={room.slug ?? room.id ?? i} style={{ width: cardWidth }}>
            <PropertyCard room={room} onPress={() => openRoom(room)} />
          </View>
        ))}
      </View>
      {!loading && !filtered.length ? (
        <Empty
          title={
            rooms.length ? "Kamar belum ditemukan" : "Katalog sedang disiapkan"
          }
          description={
            rooms.length
              ? "Coba nama tipe atau pilih properti lainnya."
              : "Pilihan kamar akan tampil setelah pengelola menyiapkan data."
          }
          action={
            rooms.length ? (
              <Button
                title="Tampilkan semua kamar"
                secondary
                onPress={() => {
                  setQ("");
                  setFilter("Semua");
                }}
              />
            ) : undefined
          }
        />
      ) : null}
      <Card style={{ backgroundColor: c.cream, borderColor: c.cream }}>
        <Text style={s.eyebrow}>Langkah berikutnya</Text>
        <Text style={s.h2}>Sudah menemukan yang cocok?</Text>
        <Text style={s.body}>
          Setelah survey dan sepakat dengan pengelola, mulai pendaftaran
          penghuni Anda di sini.
        </Text>
        <Button
          title="Sudah survey? Daftar penghuni"
          icon="arrow-forward-outline"
          onPress={() => props.navigate("intake")}
        />
      </Card>
      {data ? <PublicInfo data={data} /> : null}
      <Button
        title="Layanan penghuni"
        secondary
        onPress={() =>
          props.navigate(
            props.session?.role === "resident" ? "resident" : "residentAccess",
          )
        }
      />
    </Page>
  );
}
export function RoomScreen(props: ScreenProps & { params: Row }) {
  const { data, error, loading, reload } = useData<Row>(
    "catalog/room/" + encodeURIComponent(props.params.slug),
  );
  const room = data?.room ?? data ?? props.params.room;
  const [selected, setSelected] = useState(0);
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const layoutWidth = width - insets.left - insets.right;
  const gallery = rowsOf(data?.photos ?? data?.gallery ?? room?.gallery ?? []);
  const photos = gallery.length
    ? gallery.map((p) => p.src ?? p.url).filter(Boolean)
    : photoOf(room ?? {})
      ? [photoOf(room)]
      : [];
  const hero = photos[selected] ?? photos[0];
  return (
    <Page
      title="Detail hunian"
      back={props.back}
      refreshing={loading}
      onRefresh={reload}
      footer={
        room ? (
          <View style={[s.row, { justifyContent: "space-between" }]}>
            <View style={{ flex: 1, minWidth: 120 }}>
              <Text style={s.muted}>Sewa per bulan</Text>
              <Text style={s.h3}>
                {room.price_label ?? money(room.price ?? room.monthly_price)}
              </Text>
            </View>
            <Button
              title="Jadwalkan survey"
              icon="calendar-outline"
              onPress={() => props.navigate("booking", { room })}
            />
          </View>
        ) : undefined
      }
    >
      {error ? <Notice error text={error} /> : null}
      {loading && !room ? <ActivityIndicator color={c.tealDark} /> : null}
      {room ? (
        <>
          <View style={{ overflow: "hidden", borderRadius: 26 }}>
            {hero ? (
              <Image
                source={{ uri: hero }}
                accessibilityLabel={"Foto kamar " + (selected + 1)}
                style={{
                  width: "100%",
                  height: width >= 660 ? 440 : 310,
                  backgroundColor: c.soft,
                }}
                resizeMode="cover"
              />
            ) : (
              <RoomPhoto room={room} height={310} />
            )}
            <View
              style={{
                position: "absolute",
                bottom: 16,
                right: 16,
                backgroundColor: c.card,
                paddingHorizontal: 12,
                paddingVertical: 7,
                borderRadius: 10,
              }}
            >
              <Text style={s.h3}>
                {photos.length
                  ? selected + 1 + " / " + photos.length
                  : "Foto belum tersedia"}
              </Text>
            </View>
          </View>
          {photos.length > 1 ? (
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={{ gap: 10 }}
            >
              {photos.map((uri, i) => (
                <Pressable
                  key={uri + i}
                  accessibilityRole="button"
                  accessibilityLabel={"Lihat foto kamar " + (i + 1)}
                  accessibilityState={{ selected: selected === i }}
                  onPress={() => setSelected(i)}
                  style={{
                    padding: 3,
                    borderWidth: 2,
                    borderColor: selected === i ? c.tealDark : "transparent",
                    borderRadius: 16,
                  }}
                >
                  <Image
                    source={{ uri }}
                    style={{
                      width: 88,
                      height: 72,
                      borderRadius: 11,
                      backgroundColor: c.soft,
                    }}
                  />
                </Pressable>
              ))}
            </ScrollView>
          ) : null}
          <View style={{ gap: 10 }}>
            <Text style={s.eyebrow}>
              {room.property_name ?? room.code ?? room.property_code}
            </Text>
            <Text style={s.h1}>{room.name ?? room.room_label}</Text>
            <View style={s.row}>
              <Pill value={(room.available_count ?? "—") + " kamar tersedia"} />
              <Pill value="Sewa bulanan" />
            </View>
            <Text style={s.body}>
              {room.public_description ??
                room.description ??
                room.type_description}
            </Text>
          </View>
          <Card>
            <View style={s.row}>
              <Icon name="sparkles-outline" color={c.tealDark} />
              <Text style={s.h2}>Fasilitas & biaya</Text>
            </View>
            {rowsOf(room.facilities).map((f, i) => (
              <View key={i} style={s.row}>
                <View
                  style={{
                    width: 34,
                    height: 34,
                    borderRadius: 11,
                    backgroundColor: c.soft,
                    alignItems: "center",
                    justifyContent: "center",
                  }}
                >
                  <Icon name="checkmark" size={16} color={c.tealDark} />
                </View>
                <Text style={[s.body, { flex: 1 }]}>
                  {f.name ?? f.label ?? f.id}
                </Text>
              </View>
            ))}
            <KV
              title="Estimasi deposit"
              value={money(room.deposit_estimate ?? room.deposit)}
            />
            <Notice text="Konfirmasi harga, periode, ketersediaan dan kondisi kamar bersama pengelola saat survey." />
          </Card>
          <Card style={{ backgroundColor: c.cream, borderColor: c.cream }}>
            <Text style={s.h2}>Mulai cerita Anda di sini.</Text>
            <Text style={s.body}>
              Sudah survey dan sepakat? Lanjutkan dengan identitas awal serta
              dokumen yang diperlukan.
            </Text>
            <Button
              title="Sudah sepakat? Isi identitas awal"
              onPress={() => props.navigate("intake", { room })}
            />
          </Card>
        </>
      ) : null}
    </Page>
  );
}
export function BookingScreen(props: ScreenProps & { params: Row }) {
  const [done, setDone] = useState(false);
  const fields: Field[] = [
    { key: "full_name", label: "Nama lengkap", required: true },
    { key: "phone", label: "Nomor WhatsApp", type: "tel", required: true },
    {
      key: "room_interest",
      label: "Kamar diminati",
      value: props.params.room?.name ?? "",
    },
    {
      key: "planned_start",
      label: "Rencana masuk",
      type: "date",
      required: true,
    },
    { key: "notes", label: "Catatan survey", type: "textarea" },
  ];
  return (
    <Page title="Jadwalkan survey" back={props.back}>
      <Card>
        {done ? (
          <>
            <Notice text="Permintaan survey berhasil diterima. Pengelola akan menindaklanjuti melalui kontak yang Anda berikan." />
            <Button
              title="Kembali mencari kamar"
              onPress={() => props.navigate("catalog")}
            />
          </>
        ) : (
          <>
            <Text style={s.h2}>Lihat kamar secara langsung</Text>
            <Text style={s.muted}>
              Permintaan survey belum mengunci reservasi kamar.
            </Text>
            <NativeForm
              fields={fields}
              submitLabel="Kirim permintaan survey"
              onSubmit={async (v) => {
                await apiPost("catalog/booking", v);
                setDone(true);
              }}
            />
          </>
        )}
      </Card>
    </Page>
  );
}
