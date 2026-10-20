import { Platform } from "react-native";
import * as Print from "expo-print";
import * as Sharing from "expo-sharing";
import * as FileSystem from "expo-file-system/legacy";
import { Row } from "../theme";
import { apiGet } from "../api/client";

export async function printFacilityForm(data: Row, stage: string) {
  const id = data.case?.id;
  if (!id)
    throw new Error(
      "Data masa tinggal belum tersedia. Muat ulang lalu coba kembali.",
    );
  const original = await apiGet<Row>("admin/lifecycle/" + id + "/print", {
    stage,
  });
  if (typeof original.html !== "string" || !original.html.trim()) {
    throw new Error(
      "Formulir cetak resmi belum tersedia. Muat ulang lalu coba kembali.",
    );
  }
  await printHtml(original.html);
}

export async function printHtml(html: string) {
  if (Platform.OS === "web") {
    await Print.printAsync({ html });
    return;
  }
  let temporary = "";
  try {
    const result = await Print.printToFileAsync({
      html,
      width: 595,
      height: 842,
    });
    temporary = result.uri;
    if (await Sharing.isAvailableAsync())
      await Sharing.shareAsync(result.uri, { mimeType: "application/pdf" });
    else await Print.printAsync({ uri: result.uri });
  } finally {
    if (temporary)
      await FileSystem.deleteAsync(temporary, { idempotent: true }).catch(
        () => {},
      );
  }
}
