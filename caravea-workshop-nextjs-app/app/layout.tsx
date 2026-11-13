import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: { default: "Workshop bookings", template: "%s · Workshop bookings" },
  description: "Book caravans in for a service in one of the dealer's service bays.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="en"
      className="h-full antialiased"
    >
      <body className="min-h-full flex flex-col">{children}</body>
    </html>
  );
}
