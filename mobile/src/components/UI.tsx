import React, { useState } from "react";
import {
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import Ionicons from "@expo/vector-icons/Ionicons";
import { SafeAreaView } from "react-native-safe-area-context";
import { colors as c, Row, label, uiLabel } from "../theme";
export const s = StyleSheet.create({
  page: { flex: 1, backgroundColor: c.background },
  scroll: {
    padding: 22,
    paddingTop: 14,
    paddingBottom: 32,
    gap: 22,
    width: "100%",
    maxWidth: 1200,
    alignSelf: "center",
  },
  card: {
    backgroundColor: c.card,
    borderRadius: 24,
    padding: 22,
    borderWidth: 1,
    borderColor: c.line,
    gap: 14,
  },
  h1: {
    fontFamily: "ManropeBold",
    fontSize: 32,
    lineHeight: 41,
    color: c.ink,
    letterSpacing: -1.1,
  },
  h2: {
    fontFamily: "ManropeBold",
    fontSize: 21,
    lineHeight: 29,
    color: c.ink,
    letterSpacing: -0.5,
  },
  h3: { fontFamily: "ManropeBold", fontSize: 15, lineHeight: 22, color: c.ink },
  body: {
    fontFamily: "ManropeRegular",
    fontSize: 14,
    lineHeight: 23,
    color: c.ink,
  },
  muted: {
    fontFamily: "ManropeRegular",
    fontSize: 13,
    lineHeight: 21,
    color: c.muted,
  },
  eyebrow: {
    fontFamily: "ManropeBold",
    fontSize: 10,
    lineHeight: 16,
    letterSpacing: 1.9,
    textTransform: "uppercase",
    color: c.tealDark,
  },
  row: {
    flexDirection: "row",
    gap: 12,
    alignItems: "center",
    flexWrap: "wrap",
  },
  input: {
    minHeight: 54,
    borderWidth: 1,
    borderColor: c.line,
    borderRadius: 16,
    padding: 15,
    fontFamily: "ManropeRegular",
    fontSize: 16,
    color: c.ink,
    backgroundColor: "#FAFAF7",
  },
  button: {
    backgroundColor: c.tealDark,
    minHeight: 52,
    borderRadius: 16,
    paddingHorizontal: 19,
    paddingVertical: 14,
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
    gap: 9,
  },
  buttonText: {
    fontFamily: "ManropeBold",
    fontSize: 14,
    lineHeight: 22,
    color: "white",
    flexShrink: 1,
    textAlign: "center",
  },
  pill: {
    backgroundColor: c.soft,
    borderRadius: 9,
    paddingHorizontal: 10,
    paddingVertical: 6,
    alignSelf: "flex-start",
  },
  pillText: {
    fontFamily: "ManropeMedium",
    fontSize: 11,
    lineHeight: 17,
    color: c.tealDark,
  },
  divider: { height: 1, backgroundColor: c.line, marginVertical: 8 },
  error: { backgroundColor: "#FFF1EC", borderColor: "#D9B6A8" },
  banner: { padding: 18, borderRadius: 16, backgroundColor: c.soft, gap: 8 },
  iconButton: {
    width: 48,
    height: 48,
    justifyContent: "center",
    alignItems: "center",
    borderRadius: 16,
    backgroundColor: c.card,
    borderWidth: 1,
    borderColor: c.line,
  },
  nav: {
    flexDirection: "row",
    padding: 8,
    gap: 4,
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: c.line,
    borderRadius: 24,
    marginHorizontal: 18,
    marginBottom: 8,
    maxWidth: 650,
    width: "94%",
    alignSelf: "center",
    shadowColor: "#302D23",
    shadowOpacity: 0.07,
    shadowRadius: 20,
    shadowOffset: { width: 0, height: 4 },
    elevation: 6,
  },
  navItem: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    gap: 4,
    minHeight: 54,
    borderRadius: 17,
  },
  navText: {
    fontFamily: "ManropeMedium",
    fontSize: 11,
    lineHeight: 17,
    color: c.muted,
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 22,
    paddingVertical: 14,
    gap: 13,
    width: "100%",
    maxWidth: 1200,
    alignSelf: "center",
  },
  logo: { width: 43, height: 43, resizeMode: "contain" },
  kv: {
    gap: 4,
    paddingVertical: 8,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: c.line,
  },
  kvLabel: {
    fontFamily: "ManropeRegular",
    fontSize: 11,
    lineHeight: 18,
    color: c.muted,
  },
  kvValue: {
    fontFamily: "ManropeMedium",
    fontSize: 14,
    lineHeight: 23,
    color: c.ink,
  },
  overlay: {
    flex: 1,
    backgroundColor: "#282D2566",
    justifyContent: "flex-end",
  },
  sheet: {
    backgroundColor: c.card,
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    maxHeight: "92%",
    paddingTop: 14,
    width: "100%",
    maxWidth: 780,
    alignSelf: "center",
  },
  sheetBody: { padding: 22, gap: 18, paddingBottom: 40 },
  link: { fontFamily: "ManropeBold", fontSize: 14, color: c.tealDark },
  empty: {
    padding: 30,
    alignItems: "center",
    gap: 13,
    backgroundColor: c.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: c.line,
  },
  thumb: { width: 68, height: 68, borderRadius: 16, backgroundColor: c.soft },
  stickyFooter: {
    backgroundColor: c.card,
    borderTopWidth: 1,
    borderTopColor: c.line,
    padding: 16,
    gap: 12,
    width: "100%",
    maxWidth: 1200,
    alignSelf: "center",
  },
  sectionHeading: {
    flexDirection: "row",
    alignItems: "flex-end",
    justifyContent: "space-between",
    gap: 16,
  },
  display: {
    fontFamily: "ManropeBold",
    fontSize: 39,
    lineHeight: 46,
    letterSpacing: -1.6,
    color: c.ink,
  },
});

export function Icon({
  name,
  size = 20,
  color = c.ink,
}: {
  name: any;
  size?: number;
  color?: string;
}) {
  return (
    <Ionicons
      name={name}
      size={size}
      color={color}
      accessible={false}
      aria-hidden={true}
      accessibilityElementsHidden
      importantForAccessibility="no"
    />
  );
}
export function Button({
  title,
  onPress,
  loading = false,
  secondary = false,
  disabled = false,
  icon,
  testID,
  accessibilityLabel,
}: {
  title: string;
  onPress: () => void;
  loading?: boolean;
  secondary?: boolean;
  disabled?: boolean;
  icon?: string;
  testID?: string;
  accessibilityLabel?: string;
}) {
  return (
    <Pressable
      testID={testID}
      accessibilityRole="button"
      accessibilityLabel={uiLabel(accessibilityLabel ?? title)}
      accessibilityState={{ disabled: disabled || loading, busy: loading }}
      onPress={onPress}
      disabled={disabled || loading}
      style={({ pressed }) => [
        s.button,
        secondary && { backgroundColor: c.soft },
        (pressed || disabled || loading) && { opacity: 0.6 },
      ]}
    >
      {loading ? (
        <ActivityIndicator color={secondary ? c.tealDark : "white"} />
      ) : icon ? (
        <Icon name={icon} color={secondary ? c.tealDark : "white"} />
      ) : null}
      <Text style={[s.buttonText, secondary && { color: c.tealDark }]}>
        {loading ? "Memproses…" : uiLabel(title)}
      </Text>
    </Pressable>
  );
}
export function Card({
  children,
  style,
}: {
  children: React.ReactNode;
  style?: any;
}) {
  return <View style={[s.card, style]}>{children}</View>;
}
export function Pill({
  value,
  warning = false,
}: {
  value: any;
  warning?: boolean;
}) {
  return (
    <View style={[s.pill, warning && { backgroundColor: "#FFF1D9" }]}>
      <Text style={[s.pillText, warning && { color: c.warning }]}>
        {label(value)}
      </Text>
    </View>
  );
}
export function Notice({
  text,
  error = false,
}: {
  text: string;
  error?: boolean;
}) {
  return (
    <View
      accessibilityRole="alert"
      style={[
        s.banner,
        {
          borderWidth: 1,
          borderColor: c.line,
          flexDirection: "row",
          alignItems: "flex-start",
          gap: 12,
        },
        error && s.error,
      ]}
    >
      <Icon
        name={error ? "alert-circle-outline" : "information-circle-outline"}
        color={error ? c.danger : c.tealDark}
      />
      <Text style={[s.body, { flex: 1 }, error && { color: c.danger }]}>
        {text}
      </Text>
    </View>
  );
}
export function KV({ title, value }: { title: string; value: any }) {
  return (
    <View style={s.kv}>
      <Text style={s.kvLabel}>{uiLabel(title)}</Text>
      <Text selectable style={s.kvValue}>
        {value === null || value === undefined || value === ""
          ? "—"
          : String(value)}
      </Text>
    </View>
  );
}
export function Empty({
  title = "Belum ada data",
  description,
  action,
}: {
  title?: string;
  description?: string;
  action?: React.ReactNode;
}) {
  return (
    <View style={s.empty}>
      <View
        style={[
          s.iconButton,
          { width: 64, height: 64, borderRadius: 22, backgroundColor: c.soft },
        ]}
      >
        <Icon name="file-tray-outline" size={28} color={c.tealDark} />
      </View>
      <Text style={s.h3}>{title}</Text>
      {description ? (
        <Text style={[s.muted, { textAlign: "center" }]}>{description}</Text>
      ) : null}
      {action}
    </View>
  );
}
export function Page({
  children,
  title,
  subtitle,
  back,
  refreshing = false,
  onRefresh,
  right,
  footer,
}: {
  children: React.ReactNode;
  title: string;
  subtitle?: string;
  back?: () => void;
  refreshing?: boolean;
  onRefresh?: () => void;
  right?: React.ReactNode;
  footer?: React.ReactNode;
}) {
  return (
    <SafeAreaView edges={["top", "left", "right"]} style={s.page}>
      <View style={s.header}>
        {back ? (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Kembali"
            onPress={back}
            style={s.iconButton}
          >
            <Icon name="arrow-back" />
          </Pressable>
        ) : (
          <Image
            source={require("../../assets/gulhouse-logo.png")}
            style={s.logo}
          />
        )}
        <View style={{ flex: 1 }}>
          <Text style={s.eyebrow}>{subtitle || "GUL HOUSE"}</Text>
          <Text
            accessibilityRole="header"
            style={[s.h3, { fontSize: 18, lineHeight: 26 }]}
          >
            {uiLabel(title)}
          </Text>
        </View>
        {right ??
          (onRefresh ? (
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Muat ulang"
              style={s.iconButton}
              onPress={onRefresh}
            >
              <Icon name="refresh-outline" />
            </Pressable>
          ) : null)}
      </View>
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === "ios" ? "padding" : undefined}
      >
        <ScrollView
          keyboardShouldPersistTaps="handled"
          contentContainerStyle={s.scroll}
          refreshControl={
            onRefresh ? (
              <RefreshControl
                refreshing={refreshing}
                onRefresh={onRefresh}
                tintColor={c.teal}
              />
            ) : undefined
          }
        >
          {children}
        </ScrollView>
      </KeyboardAvoidingView>
      {footer ? <View style={s.stickyFooter}>{footer}</View> : null}
    </SafeAreaView>
  );
}
export function Sheet({
  visible,
  title,
  onClose,
  children,
}: {
  visible: boolean;
  title: string;
  onClose: () => void;
  children: React.ReactNode;
}) {
  return (
    <Modal
      transparent
      visible={visible}
      animationType="slide"
      onRequestClose={onClose}
    >
      <View accessibilityViewIsModal style={s.overlay}>
        <Pressable
          accessibilityLabel="Tutup panel"
          onPress={onClose}
          style={{ flex: 1, minHeight: 15 }}
        />
        <View style={s.sheet}>
          <View
            accessible={false}
            style={{
              width: 38,
              height: 4,
              borderRadius: 4,
              backgroundColor: c.line,
              alignSelf: "center",
              marginBottom: 15,
            }}
          />
          <View
            style={[
              s.row,
              { paddingHorizontal: 20, justifyContent: "space-between" },
            ]}
          >
            <Text style={s.h2}>{uiLabel(title)}</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Tutup"
              style={s.iconButton}
              onPress={onClose}
            >
              <Icon name="close" />
            </Pressable>
          </View>
          <ScrollView
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={s.sheetBody}
          >
            {children}
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
}
export function Accordion({
  title,
  children,
  initial = false,
}: {
  title: string;
  children: React.ReactNode;
  initial?: boolean;
}) {
  const [open, setOpen] = useState(initial);
  return (
    <Card>
      <Pressable
        accessibilityRole="button"
        accessibilityState={{ expanded: open }}
        onPress={() => setOpen(!open)}
        style={[s.row, { justifyContent: "space-between", minHeight: 48 }]}
      >
        <Text style={[s.h3, { flex: 1 }]}>{title}</Text>
        <Icon name={open ? "chevron-up" : "chevron-down"} />
      </Pressable>
      {open ? children : null}
    </Card>
  );
}
export function DataList({ row, columns }: { row: Row; columns: Row }) {
  return (
    <>
      {Object.entries(columns).map(([key, name]) => (
        <KV key={key} title={String(name)} value={row[key]} />
      ))}
    </>
  );
}
export function RecordDetails({ value }: { value: any }) {
  if (value === null || value === undefined)
    return <Text style={s.muted}>Tidak ada data</Text>;
  if (typeof value !== "object")
    return (
      <Text selectable style={s.body}>
        {String(value)}
      </Text>
    );
  if (Array.isArray(value))
    return (
      <View style={{ gap: 12 }}>
        {value.length ? (
          value.map((item, index) => (
            <View key={index} style={s.banner}>
              <RecordDetails value={item} />
            </View>
          ))
        ) : (
          <Text style={s.muted}>Belum ada data</Text>
        )}
      </View>
    );
  return (
    <View style={{ gap: 9 }}>
      {Object.entries(value)
        .filter(([key]) => !["schema", "options", "actions"].includes(key))
        .map(([key, item]) =>
          typeof item === "object" && item !== null ? (
            <Accordion key={key} title={label(key)}>
              <RecordDetails value={item} />
            </Accordion>
          ) : (
            <KV key={key} title={label(key)} value={item} />
          ),
        )}
    </View>
  );
}
export function ProgressSteps({
  steps,
}: {
  steps: { title: string; done: boolean }[];
}) {
  const current = steps.findIndex((step) => !step.done);
  return (
    <View style={{ gap: 14 }}>
      <Text style={s.eyebrow}>Perjalanan masa tinggal</Text>
      <View style={{ flexDirection: "row", gap: 3 }}>
        {steps.map((step, index) => (
          <View
            key={step.title}
            style={{ flex: 1, alignItems: "center", gap: 8 }}
          >
            <View
              style={{
                width: 31,
                height: 31,
                borderRadius: 12,
                backgroundColor: step.done
                  ? c.tealDark
                  : index === current
                    ? c.cream
                    : c.soft,
                alignItems: "center",
                justifyContent: "center",
                borderWidth: 1,
                borderColor: index === current ? c.accent : "transparent",
              }}
            >
              {step.done ? (
                <Icon name="checkmark" size={15} color="white" />
              ) : (
                <Text
                  style={[
                    s.pillText,
                    { color: index === current ? c.ink : c.muted },
                  ]}
                >
                  {index + 1}
                </Text>
              )}
            </View>
            <Text
              style={[
                s.muted,
                {
                  fontSize: 11,
                  lineHeight: 17,
                  textAlign: "center",
                  color: step.done || index === current ? c.ink : c.muted,
                },
              ]}
            >
              {step.title}
            </Text>
          </View>
        ))}
      </View>
    </View>
  );
}
