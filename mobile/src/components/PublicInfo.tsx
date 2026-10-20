import React, { useState } from "react";
import { Linking, Text, View } from "react-native";
import { Accordion, Button, Card, Icon, Notice, s } from "./UI";
import { colors as c, Row, rowsOf } from "../theme";
export function PublicInfo({ data }: { data: Row }) {
  const [error, setError] = useState("");
  const info = data.public_info ?? {},
    contacts = info.contacts ?? data.contacts ?? {};
  async function open(url: string) {
    try {
      if (!/^https?:\/\//i.test(url))
        throw new Error("Tautan kontak belum tersedia.");
      await Linking.openURL(url);
    } catch (e: any) {
      setError(e.message || "Tautan belum dapat dibuka.");
    }
  }
  return (
    <>
      {error ? <Notice error text={error} /> : null}
      {rowsOf(data.amenities).length ? (
        <Accordion title="Fasilitas & layanan GulHouse">
          {rowsOf(data.amenities).map((item, index) => (
            <View key={index} style={s.banner}>
              <View style={s.row}>
                <Icon name="checkmark-circle-outline" color={c.teal} />
                <Text style={[s.h3, { flex: 1 }]}>
                  {item.name ?? item.label}
                </Text>
              </View>
              <Text style={s.muted}>{item.description}</Text>
            </View>
          ))}
        </Accordion>
      ) : null}
      {rowsOf(info.locations).length ? (
        <Accordion title="Lokasi properti">
          {rowsOf(info.locations).map((location, index) => (
            <Card key={index}>
              <Text style={s.h3}>{location.name ?? location.code}</Text>
              <Text style={s.body}>{location.address}</Text>
              {location.maps_url ? (
                <Button
                  secondary
                  title="Buka petunjuk arah"
                  icon="navigate-outline"
                  onPress={() => open(location.maps_url)}
                />
              ) : null}
            </Card>
          ))}
        </Accordion>
      ) : null}
      {rowsOf(data.house_rules).length ? (
        <Accordion title="Tata tertib penghuni">
          {rowsOf(data.house_rules).map((rule, index) => (
            <Text key={index} style={s.body}>
              {index + 1}.{" "}
              {rule.text ??
                rule.rule ??
                rule.description ??
                rule.title ??
                rule.label}
            </Text>
          ))}
        </Accordion>
      ) : null}
      {rowsOf(info.faq).map((faq, index) => (
        <Accordion key={index} title={faq.question}>
          <Text style={s.body}>{faq.answer}</Text>
        </Accordion>
      ))}
      <Card>
        <Text style={s.h2}>Hubungi GulHouse</Text>
        <Text style={s.muted}>
          Konfirmasikan survey, kamar dan informasi sewa melalui kontak resmi
          pengelola.
        </Text>
        {contacts.whatsapp_url ? (
          <Button
            title="WhatsApp pengelola"
            icon="logo-whatsapp"
            onPress={() => open(contacts.whatsapp_url)}
          />
        ) : null}
        {contacts.instagram_url ? (
          <Button
            title="Instagram GulHouse"
            secondary
            icon="logo-instagram"
            onPress={() => open(contacts.instagram_url)}
          />
        ) : null}
        {!contacts.whatsapp_url && !contacts.instagram_url ? (
          <Text style={s.muted}>
            Kontak resmi belum dikonfigurasi. Kirim permintaan survey melalui
            aplikasi.
          </Text>
        ) : null}
      </Card>
    </>
  );
}
