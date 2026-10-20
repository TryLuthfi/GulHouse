import { Platform } from "react-native";
import * as ImageManipulator from "expo-image-manipulator";
import * as FileSystem from "expo-file-system/legacy";
import { apiUpload, getApiBase, getAuthToken } from "../api/client";
import { Row } from "../theme";
export async function rotatePhoto(photo: Row, degrees: number) {
  if (![90, 180, 270].includes(degrees))
    throw new Error("Pilih rotasi 90°, 180°, atau 270°.");
  const url = String(photo.url || "");
  if (!url.startsWith(getApiBase() + "/"))
    throw new Error(
      "Alamat foto tidak sesuai server aplikasi. Muat ulang daftar foto.",
    );
  const filename = String(photo.file ?? photo.filename ?? "");
  const ext = filename.split(".").pop()?.toLowerCase();
  const format =
    ext === "png"
      ? ImageManipulator.SaveFormat.PNG
      : ext === "webp"
        ? ImageManipulator.SaveFormat.WEBP
        : ImageManipulator.SaveFormat.JPEG;
  const mime =
    ext === "png" ? "image/png" : ext === "webp" ? "image/webp" : "image/jpeg";
  let source = "",
    temporary = "",
    webObjectUrl = "",
    rotated = "";
  try {
    if (Platform.OS === "web") {
      const response = await fetch(url, {
        headers: { Authorization: "Bearer " + getAuthToken() },
      });
      if (!response.ok)
        throw new Error("Foto belum dapat dimuat untuk rotasi.");
      webObjectUrl = URL.createObjectURL(await response.blob());
      source = webObjectUrl;
    } else {
      temporary =
        (FileSystem.cacheDirectory ?? FileSystem.documentDirectory) +
        "gulhouse-rotate-" +
        Date.now() +
        "." +
        (ext || "jpg");
      await FileSystem.downloadAsync(url, temporary, {
        headers: { Authorization: "Bearer " + getAuthToken() },
      });
      source = temporary;
    }
    const result = await ImageManipulator.manipulateAsync(
      source,
      [{ rotate: degrees }],
      { format, compress: 1 },
    );
    rotated = result.uri;
    const file =
      Platform.OS === "web" ? await (await fetch(rotated)).blob() : undefined;
    return await apiUpload<Row>(
      "admin/photos/0/rotate",
      {
        scope: photo.scope,
        property: photo.property ?? "",
        type: photo.type ?? "",
        file: filename,
        degrees,
      },
      { rotated_file: { uri: rotated, name: filename, mimeType: mime, file } },
    );
  } finally {
    if (webObjectUrl) URL.revokeObjectURL(webObjectUrl);
    if (Platform.OS !== "web") {
      if (temporary)
        await FileSystem.deleteAsync(temporary, { idempotent: true }).catch(
          () => {},
        );
      if (rotated)
        await FileSystem.deleteAsync(rotated, { idempotent: true }).catch(
          () => {},
        );
    }
  }
}
