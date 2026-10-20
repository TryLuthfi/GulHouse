import React, { useEffect, useRef, useState } from "react";
import {
  ActivityIndicator,
  BackHandler,
  Platform,
  Pressable,
  Text,
  View,
} from "react-native";
import { SafeAreaProvider, SafeAreaView } from "react-native-safe-area-context";
import { StatusBar } from "expo-status-bar";
import { useFonts } from "expo-font";
import {
  apiGet,
  apiPost,
  getApiBase,
  onSessionExpired,
  persistSession,
  restoreSession,
  setApiBase,
  setAuthToken,
} from "./src/api/client";
import { Button, Card, Icon, Notice, Page, s } from "./src/components/UI";
import { NativeForm } from "./src/components/NativeForm";
import { AdminModuleScreen, AdminScreen } from "./src/screens/Admin";
import {
  CatalogScreen,
  RoomScreen,
  BookingScreen,
} from "./src/screens/Catalog";
import {
  AccessScreen,
  IntakeScreen,
  ResidentScreen,
} from "./src/screens/Resident";
import { LifecycleScreen } from "./src/screens/Lifecycle";
import { PaymentDetailScreen } from "./src/screens/Payments";
import {
  PaymentLookupScreen,
  PaymentHistoryScreen,
} from "./src/screens/PaymentLookup";
import { colors as c, Row, ScreenProps, Session } from "./src/theme";
type Route = { screen: string; params: Row };
export default function App() {
  const [fontsLoaded, fontError] = useFonts({
    ManropeRegular: require("./assets/fonts/Manrope_400Regular.ttf"),
    ManropeMedium: require("./assets/fonts/Manrope_500Medium.ttf"),
    ManropeBold: require("./assets/fonts/Manrope_700Bold.ttf"),
  });
  const [session, setSession] = useState<Session | null>(null),
    [stack, setStack] = useState<Route[]>([{ screen: "catalog", params: {} }]),
    [ready, setReady] = useState(false);
  const route = stack[stack.length - 1];
  const stackRef = useRef(stack);
  stackRef.current = stack;
  const navigate = (screen: string, params: Row = {}) =>
    setStack((old) =>
      ["catalog", "resident", "admin", "settings"].includes(screen)
        ? [{ screen, params }]
        : [...old, { screen, params }],
    );
  const back = () =>
    setStack((old) =>
      old.length > 1 ? old.slice(0, -1) : [{ screen: "catalog", params: {} }],
    );
  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const restored = await restoreSession();
        if (restored.token) {
          setAuthToken(restored.token);
          const me = await apiGet<Row>("me");
          const user = me.user ?? me;
          const kind = user.kind ?? me.kind;
          if (active && ["admin", "resident", "payment"].includes(kind))
            setSession({ token: restored.token, role: kind, user });
        }
      } catch {
        setAuthToken(null);
        await persistSession(null);
      } finally {
        if (active) setReady(true);
      }
    })();
    return () => {
      active = false;
    };
  }, []);
  useEffect(
    () =>
      onSessionExpired(() => {
        const target =
          session?.role === "admin"
            ? "adminLogin"
            : session?.role === "resident"
              ? "residentAccess"
              : session?.role === "payment"
                ? "paymentLookup"
                : "catalog";
        setSession(null);
        setStack([{ screen: target, params: {} }]);
      }),
    [session?.role],
  );
  useEffect(() => {
    const handler = BackHandler.addEventListener("hardwareBackPress", () => {
      console.info("GH_BACK_DEPTH", stackRef.current.length);
      if (stackRef.current.length > 1) {
        back();
        return true;
      }
      return false;
    });
    return () => handler.remove();
  }, []);
  const onAuth = (value: Session) => {
    setAuthToken(value.token);
    setSession(value);
    persistSession(value.token).catch(() => {});
  };
  async function onLogout() {
    // Dispatch while the old bearer is still current; request transport captures it.
    const revoke = apiPost("auth/logout", {}).catch(() => {});
    setAuthToken(null);
    setSession(null);
    navigate("catalog");
    await persistSession(null);
    await revoke;
  }
  const props: ScreenProps = { navigate, back, session, onAuth, onLogout };
  let content: React.ReactNode;
  if (!ready || (!fontsLoaded && !fontError))
    content = (
      <View
        style={[
          s.page,
          { justifyContent: "center", alignItems: "center", gap: 15 },
        ]}
      >
        <ActivityIndicator color={c.teal} />
        <Text style={s.muted}>Memuat GulHouse…</Text>
      </View>
    );
  else
    switch (route.screen) {
      case "room":
        content = <RoomScreen {...props} params={route.params} />;
        break;
      case "booking":
        content = <BookingScreen {...props} params={route.params} />;
        break;
      case "intake":
        content = <IntakeScreen {...props} />;
        break;
      case "residentAccess":
        content = <AccessScreen {...props} kind="resident" />;
        break;
      case "adminLogin":
        content = <AccessScreen {...props} kind="admin" />;
        break;
      case "resident":
        content =
          session?.role === "resident" ? (
            <ResidentScreen {...props} params={route.params} />
          ) : (
            <AccessScreen {...props} kind="resident" />
          );
        break;
      case "admin":
        content =
          session?.role === "admin" ? (
            <AdminScreen {...props} />
          ) : (
            <AccessScreen {...props} kind="admin" />
          );
        break;
      case "module":
        content =
          session?.role === "admin" ? (
            <AdminModuleScreen {...props} params={route.params} />
          ) : (
            <AccessScreen {...props} kind="admin" />
          );
        break;
      case "paymentLookup":
        content = <PaymentLookupScreen {...props} />;
        break;
      case "paymentHistory":
        content =
          session?.role === "payment" ? (
            <PaymentHistoryScreen {...props} />
          ) : (
            <PaymentLookupScreen {...props} />
          );
        break;
      case "paymentDetail":
        content =
          session?.role === "admin" ? (
            <PaymentDetailScreen {...props} params={route.params} />
          ) : (
            <AccessScreen {...props} kind="admin" />
          );
        break;
      case "adminLifecycle":
        content =
          session?.role === "admin" ? (
            <LifecycleScreen {...props} params={route.params} />
          ) : (
            <AccessScreen {...props} kind="admin" />
          );
        break;
      case "settings":
        content = (
          <SettingsScreen
            {...props}
            onReset={() => {
              setSession(null);
            }}
          />
        );
        break;
      default:
        content = <CatalogScreen {...props} />;
    }
  const active =
    route.screen === "catalog" || ["room", "booking"].includes(route.screen)
      ? "catalog"
      : [
            "resident",
            "residentAccess",
            "intake",
            "paymentLookup",
            "paymentHistory",
          ].includes(route.screen)
        ? "resident"
        : [
              "admin",
              "adminLogin",
              "module",
              "adminLifecycle",
              "paymentDetail",
            ].includes(route.screen)
          ? "admin"
          : "settings";
  return (
    <SafeAreaProvider>
      <StatusBar style="dark" />
      <View style={{ flex: 1, backgroundColor: c.background }}>
        {content}
        {ready && (fontsLoaded || fontError) ? (
          <SafeAreaView
            edges={["bottom"]}
            style={{ backgroundColor: c.background }}
          >
            <View style={s.nav}>
              {[
                { key: "catalog", name: "Kamar", icon: "home-outline" },
                { key: "resident", name: "Penghuni", icon: "person-outline" },
                { key: "admin", name: "Pengelola", icon: "grid-outline" },
                {
                  key: "settings",
                  name: "Lainnya",
                  icon: "ellipsis-horizontal",
                },
              ].map((tab) => (
                <Pressable
                  key={tab.key}
                  accessibilityRole="tab"
                  accessibilityLabel={tab.name}
                  accessibilityState={{ selected: active === tab.key }}
                  onPress={() =>
                    navigate(
                      tab.key === "resident" && session?.role === "payment"
                        ? "paymentHistory"
                        : tab.key,
                    )
                  }
                  style={[
                    s.navItem,
                    active === tab.key && { backgroundColor: c.soft },
                  ]}
                >
                  <Icon
                    name={tab.icon}
                    size={22}
                    color={active === tab.key ? c.tealDark : c.muted}
                  />
                  <Text
                    style={[
                      s.navText,
                      active === tab.key && {
                        color: c.tealDark,
                        fontFamily: "ManropeBold",
                      },
                    ]}
                  >
                    {tab.name}
                  </Text>
                </Pressable>
              ))}
            </View>
          </SafeAreaView>
        ) : null}
      </View>
    </SafeAreaProvider>
  );
}
function SettingsScreen(props: ScreenProps & { onReset: () => void }) {
  const [message, setMessage] = useState("");
  return (
    <Page title="Pengaturan & privasi" subtitle="Atur layanan GulHouse">
      <Card style={{ backgroundColor: c.cream, borderColor: c.cream }}>
        <Icon name="options-outline" size={32} color={c.tealDark} />
        <Text style={s.h1}>Tetap terhubung, dengan tenang.</Text>
        <Text style={s.body}>
          Atur layanan yang Anda gunakan dan kenali cara GulHouse menjaga data
          pribadi Anda.
        </Text>
      </Card>
      <Card>
        <Text style={s.h2}>Alamat layanan</Text>
        <Text style={s.body}>
          Ubah hanya jika pengelola memberi alamat layanan baru. Mengganti
          layanan akan meminta Anda masuk kembali.
        </Text>
        <NativeForm
          fields={[
            { key: "base", label: "Alamat layanan GulHouse", required: true },
          ]}
          initial={{ base: getApiBase() }}
          submitLabel="Simpan alamat layanan"
          onSubmit={async (v) => {
            const url = String(v.base).trim();
            if (!/^https?:\/\//i.test(url))
              throw new Error("Alamat harus diawali https:// atau http://");
            setApiBase(url);
            await persistSession(null);
            props.onReset();
            setMessage(
              "Koneksi disimpan. Masuk kembali untuk mengakses data pribadi.",
            );
          }}
        />
        {message ? <Notice text={message} /> : null}
      </Card>
      <Card>
        <Text style={s.h2}>Privasi & akses</Text>
        <Text style={s.body}>
          Dokumen identitas dan bukti pembayaran hanya diakses melalui sesi yang
          berwenang. Upload bukti tidak otomatis memverifikasi transaksi.
          Pemeriksaan fasilitas fisik tetap memerlukan tanda tangan dan
          verifikasi pengelola.
        </Text>
        <Text style={s.muted}>GulHouse · Layanan penghuni dan pengelola</Text>
        {props.session ? (
          <Button secondary title="Keluar dari akun" onPress={props.onLogout} />
        ) : null}
      </Card>
    </Page>
  );
}
