import React, { useEffect, useState } from "react";
import { ActivityIndicator, Image, Platform, View } from "react-native";
import { getApiBase, getAuthToken } from "../api/client";
import { colors as c } from "../theme";
import { Icon } from "./UI";
export function SecureImage({
  uri,
  height = 190,
}: {
  uri: string;
  height?: number;
}) {
  const [resolved, setResolved] = useState<string | null>(
      Platform.OS === "web" ? null : uri,
    ),
    [failed, setFailed] = useState(false);
  const privateImage = uri.startsWith(getApiBase() + "/");
  useEffect(() => {
    let live = true,
      url: string | undefined;
    if (Platform.OS !== "web") {
      setResolved(uri);
      return;
    }
    if (!privateImage) {
      setResolved(uri);
      return;
    }
    fetch(uri, { headers: { Authorization: "Bearer " + getAuthToken() } })
      .then((r) => {
        if (!r.ok) throw new Error("Foto tidak tersedia");
        return r.blob();
      })
      .then((blob) => {
        url = URL.createObjectURL(blob);
        if (live) setResolved(url);
      })
      .catch(() => {
        if (live) setFailed(true);
      });
    return () => {
      live = false;
      if (url) URL.revokeObjectURL(url);
    };
  }, [uri, privateImage]);
  if (failed)
    return (
      <View
        style={{
          height,
          borderRadius: 16,
          backgroundColor: c.soft,
          alignItems: "center",
          justifyContent: "center",
        }}
      >
        <Icon name="image-outline" size={40} color={c.muted} />
      </View>
    );
  return resolved ? (
    <Image
      accessibilityLabel="Foto GulHouse"
      source={{
        uri: resolved,
        headers:
          privateImage && Platform.OS !== "web"
            ? { Authorization: "Bearer " + getAuthToken() }
            : undefined,
      }}
      style={{
        width: "100%",
        height,
        borderRadius: 16,
        backgroundColor: c.soft,
      }}
      resizeMode="cover"
      onError={() => setFailed(true)}
    />
  ) : (
    <View style={{ height, alignItems: "center", justifyContent: "center" }}>
      <ActivityIndicator color={c.teal} />
    </View>
  );
}
