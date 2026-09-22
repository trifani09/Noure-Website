import type { NextConfig } from "next";

const apiUrl = new URL(process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api");
const isLocalApi = ["localhost", "127.0.0.1", "::1"].includes(apiUrl.hostname);

const nextConfig: NextConfig = {
  images: {
    dangerouslyAllowLocalIP: isLocalApi,
    remotePatterns: [
      {
        protocol: apiUrl.protocol.replace(":", "") as "http" | "https",
        hostname: apiUrl.hostname,
        port: apiUrl.port,
        pathname: "/**",
      },
    ],
  },
};

export default nextConfig;
