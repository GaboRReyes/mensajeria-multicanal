import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Mensajería Multicanal",
  description: "Plataforma de mensajería multicanal",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="es">
      <body>{children}</body>
    </html>
  );
}
