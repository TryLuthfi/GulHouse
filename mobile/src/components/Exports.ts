import { Platform } from "react-native";
import * as FileSystem from "expo-file-system/legacy";
import * as Sharing from "expo-sharing";
export async function shareTextFile(
  filename: string,
  content: string,
  mime = "text/csv",
) {
  if (Platform.OS === "web") {
    const uri = URL.createObjectURL(new Blob([content], { type: mime }));
    const a = document.createElement("a");
    a.href = uri;
    a.download = filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(uri), 1000);
    return;
  }
  const path =
    (FileSystem.cacheDirectory ?? FileSystem.documentDirectory) +
    filename.replace(/[^a-zA-Z0-9_.-]/g, "_");
  try {
    await FileSystem.writeAsStringAsync(path, content, {
      encoding: FileSystem.EncodingType.UTF8,
    });
    if (!(await Sharing.isAvailableAsync()))
      throw new Error("Berbagi file belum tersedia pada perangkat ini.");
    await Sharing.shareAsync(path, { mimeType: mime, dialogTitle: filename });
  } finally {
    await FileSystem.deleteAsync(path, { idempotent: true }).catch(() => {});
  }
}
