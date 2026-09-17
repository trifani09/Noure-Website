import type { NextConfig } from "next";

const apiUrl = new URL(process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api");
const nextConfig: NextConfig = { images: { remotePatterns: [{ protocol: apiUrl.protocol.replace(":", "") as "http" | "https", hostname: apiUrl.hostname, port: apiUrl.port, pathname: "/**" }] } };

export default nextConfig;
