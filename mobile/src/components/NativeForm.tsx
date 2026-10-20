import React, { useRef, useState } from "react";
import {
  Platform,
  Pressable,
  Switch,
  Text,
  TextInput,
  View,
} from "react-native";
import * as DocumentPicker from "expo-document-picker";
import * as ImagePicker from "expo-image-picker";
import { Button, Icon, Notice, Sheet, s } from "./UI";
import {
  Row,
  colors as c,
  optionLabel,
  rowsOf,
  uiLabel,
  uiOptions,
} from "../theme";
export type NativeFile = {
  uri: string;
  name: string;
  mimeType: string;
  file?: any;
};
export type Field = {
  key: string;
  label: string;
  type?: string;
  required?: boolean;
  options?: any;
  help?: string;
  readonly?: boolean;
  value?: any;
  accept?: string;
};
const temporalFormats: Record<string, { pattern: RegExp; label: string }> = {
  date: { pattern: /^\d{4}-\d{2}-\d{2}$/, label: "YYYY-MM-DD" },
  month: { pattern: /^\d{4}-\d{2}$/, label: "YYYY-MM" },
  datetime: {
    pattern: /^\d{4}-\d{2}-\d{2} (?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/,
    label: "YYYY-MM-DD HH:mm[:ss]",
  },
};
export function Select({
  title,
  value,
  options,
  onChange,
  required = false,
}: {
  title: string;
  value: any;
  options: any;
  onChange: (v: any) => void;
  required?: boolean;
}) {
  const [open, setOpen] = useState(false),
    [search, setSearch] = useState("");
  const list = rowsOf(options);
  const selected = list.find(
    (o) => String(o.value ?? o.id ?? o.key) === String(value),
  );
  return (
    <View style={{ gap: 7 }}>
      <Text style={s.h3}>
        {title}
        {required ? " *" : ""}
      </Text>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel={title}
        style={[s.input, s.row, { justifyContent: "space-between" }]}
        onPress={() => setOpen(true)}
      >
        <Text style={[s.body, { flex: 1 }]}>
          {selected ? optionLabel(selected) : value ? String(value) : "Pilih…"}
        </Text>
        <Icon name="chevron-down" size={18} />
      </Pressable>
      <Sheet visible={open} title={title} onClose={() => setOpen(false)}>
        <TextInput
          placeholderTextColor={c.placeholder}
          accessibilityLabel={"Cari " + title}
          placeholder="Cari pilihan…"
          value={search}
          onChangeText={setSearch}
          style={s.input}
        />
        <Button
          title="Kosongkan pilihan"
          secondary
          onPress={() => {
            onChange("");
            setOpen(false);
          }}
        />
        {list
          .filter((o) =>
            optionLabel(o).toLowerCase().includes(search.toLowerCase()),
          )
          .map((option, index) => (
            <Pressable
              key={String(option.value ?? option.id ?? option.key ?? index)}
              accessibilityRole="button"
              onPress={() => {
                onChange(option.value ?? option.id ?? option.key);
                setOpen(false);
              }}
              style={[s.card, { padding: 14 }]}
            >
              <Text style={s.body}>{optionLabel(option)}</Text>
            </Pressable>
          ))}
      </Sheet>
    </View>
  );
}
export function NativeForm({
  fields: sourceFields,
  initial = {},
  onSubmit,
  submitLabel = "Simpan",
  busy = false,
  error,
  success,
}: {
  fields: Field[];
  initial?: Row;
  onSubmit: (values: Row, files: Record<string, NativeFile>) => Promise<any>;
  submitLabel?: string;
  busy?: boolean;
  error?: string;
  success?: string;
}) {
  const fields = sourceFields.map((field) => ({
    ...field,
    label: uiLabel(field.label),
    options: uiOptions(field.options),
  }));
  const [values, setValues] = useState<Row>(() => {
      const v = {
        ...Object.fromEntries(fields.map((f) => [f.key, f.value ?? ""])),
        ...initial,
      };
      fields
        .filter((f) => ["checkbox", "boolean"].includes(f.type || ""))
        .forEach((f) => {
          v[f.key] = [true, 1, "1"].includes(v[f.key]) ? 1 : 0;
        });
      return v;
    }),
    [files, setFiles] = useState<Record<string, NativeFile>>({}),
    [localError, setError] = useState(""),
    [sending, setSending] = useState(false),
    [focused, setFocused] = useState<string | null>(null);
  const submitLock = useRef(false);
  const change = (key: string, value: any) => {
    setValues((v) => ({ ...v, [key]: value }));
    setError("");
  };
  async function pick(field: Field) {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type:
          field.accept === "application/pdf"
            ? ["application/pdf"]
            : field.accept?.includes("image")
              ? field.accept.includes("webp")
                ? ["image/jpeg", "image/png", "image/webp"]
                : ["image/jpeg", "image/png"]
              : ["application/pdf", "image/jpeg", "image/png"],
        copyToCacheDirectory: true,
        multiple: false,
      });
      if (result.canceled) return;
      const a = result.assets[0];
      if (a.size && a.size > 10 * 1024 * 1024) {
        setError("Ukuran dokumen maksimal 10 MB.");
        return;
      }
      setFiles((f) => ({
        ...f,
        [field.key]: {
          uri: a.uri,
          name: a.name,
          mimeType: a.mimeType || "application/octet-stream",
          file: (a as any).file,
        },
      }));
      setError("");
    } catch (e: any) {
      setError(e.message || "Dokumen belum dapat dipilih.");
    }
  }
  async function camera(field: Field) {
    try {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (!permission.granted) {
        setError(
          "Izinkan akses kamera untuk memotret dokumen, atau pilih file yang sudah tersedia.",
        );
        return;
      }
      const result = await ImagePicker.launchCameraAsync({
        mediaTypes: ["images"],
        quality: 0.85,
      });
      if (result.canceled) return;
      const a = result.assets[0];
      setFiles((f) => ({
        ...f,
        [field.key]: {
          uri: a.uri,
          name: a.fileName || "gulhouse-" + Date.now() + ".jpg",
          mimeType: a.mimeType || "image/jpeg",
          file: (a as any).file,
        },
      }));
      setError("");
    } catch (e: any) {
      setError(
        e.message ||
          "Kamera belum dapat digunakan. Pilih file sebagai alternatif.",
      );
    }
  }
  async function submit() {
    if (submitLock.current || sending || busy) return;
    for (const f of fields) {
      if (
        (f.required &&
          !(f.type === "file"
            ? files[f.key]
            : String(values[f.key] ?? "").trim())) ||
        (f.required &&
          ["checkbox", "boolean"].includes(f.type || "") &&
          ![true, 1, "1"].includes(values[f.key]))
      ) {
        setError(f.label + " wajib diisi.");
        return;
      }
      if (
        values[f.key] &&
        temporalFormats[f.type || ""] &&
        !temporalFormats[f.type || ""].pattern.test(String(values[f.key]))
      ) {
        setError(
          f.label + " gunakan format " + temporalFormats[f.type || ""].label,
        );
        return;
      }
    }
    submitLock.current = true;
    setSending(true);
    setError("");
    try {
      await onSubmit(values, files);
    } catch (e: any) {
      setError(
        e.message || "Data belum tersimpan. Periksa koneksi lalu coba kembali.",
      );
    } finally {
      submitLock.current = false;
      setSending(false);
    }
  }
  return (
    <View style={{ gap: 17 }}>
      {fields
        .filter((f) => f.type !== "hidden")
        .map((f) => (
          <View
            key={f.key}
            style={[
              { gap: 9 },
              f.type === "file" && {
                padding: 18,
                backgroundColor: c.background,
                borderWidth: 1,
                borderColor: c.line,
                borderStyle: "dashed",
                borderRadius: 18,
              },
            ]}
          >
            {f.type === "select" || f.options ? (
              <Select
                title={f.label}
                value={values[f.key]}
                options={f.options}
                required={f.required}
                onChange={(v) => change(f.key, v)}
              />
            ) : f.type === "checkbox" || f.type === "boolean" ? (
              <View
                style={[
                  s.row,
                  {
                    minHeight: 56,
                    padding: 14,
                    backgroundColor: c.background,
                    borderRadius: 16,
                  },
                ]}
              >
                <Switch
                  accessibilityLabel={f.label}
                  value={[true, 1, "1"].includes(values[f.key])}
                  onValueChange={(v) => change(f.key, v ? 1 : 0)}
                  trackColor={{ true: c.teal, false: c.line }}
                />
                <Text style={[s.body, { flex: 1 }]}>
                  {f.label}
                  {f.required ? " *" : ""}
                </Text>
              </View>
            ) : f.type === "file" ? (
              <>
                <Text style={s.h3}>
                  {f.label}
                  {f.required ? " *" : ""}
                </Text>
                <Button
                  secondary
                  title={files[f.key]?.name || "Pilih PDF / JPG / PNG"}
                  icon="cloud-upload-outline"
                  onPress={() => pick(f)}
                />
                {files[f.key] ? (
                  <Button
                    secondary
                    title="Hapus pilihan file"
                    icon="close-circle-outline"
                    onPress={() => {
                      setFiles((current) => {
                        const next = { ...current };
                        delete next[f.key];
                        return next;
                      });
                      setError("");
                    }}
                  />
                ) : null}
                {!f.accept || f.accept.includes("image") ? (
                  <Button
                    secondary
                    title="Foto dokumen dengan kamera"
                    icon="camera-outline"
                    onPress={() => camera(f)}
                  />
                ) : null}
                <Text style={s.muted}>Maksimal 10 MB · disimpan privat</Text>
              </>
            ) : (
              <>
                <Text style={s.h3}>
                  {f.label}
                  {f.required ? " *" : ""}
                </Text>
                <TextInput
                  placeholderTextColor={c.placeholder}
                  accessibilityLabel={f.label}
                  value={
                    values[f.key] === null ? "" : String(values[f.key] ?? "")
                  }
                  onChangeText={(v) => change(f.key, v)}
                  onFocus={() => setFocused(f.key)}
                  onBlur={() => setFocused(null)}
                  editable={!f.readonly && !busy && !sending}
                  secureTextEntry={f.type === "password"}
                  autoCapitalize={
                    ["email", "password"].includes(f.type || "")
                      ? "none"
                      : "sentences"
                  }
                  keyboardType={
                    ["number", "money"].includes(f.type || "")
                      ? "numeric"
                      : f.type === "email"
                        ? "email-address"
                        : f.type === "tel"
                          ? "phone-pad"
                          : "default"
                  }
                  multiline={f.type === "textarea"}
                  style={[
                    s.input,
                    focused === f.key && {
                      borderColor: c.tealDark,
                      backgroundColor: c.card,
                    },
                    f.type === "textarea" && {
                      height: 100,
                      textAlignVertical: "top",
                    },
                    f.readonly && { backgroundColor: c.soft },
                  ]}
                  placeholder={temporalFormats[f.type || ""]?.label}
                />
              </>
            )}
            {f.help ? <Text style={s.muted}>{f.help}</Text> : null}
          </View>
        ))}
      {error || localError ? (
        <Notice error text={localError || error || ""} />
      ) : null}
      {success ? <Notice text={success} /> : null}
      <Button title={submitLabel} onPress={submit} loading={sending || busy} />
    </View>
  );
}
