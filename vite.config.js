import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.ts"],
      refresh: true,
      fonts: [
        bunny("Instrument Sans", {
          weights: [400, 500, 600],
        }),
      ],
    }),
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      // Generic, cross-domain frontend code (ui components, useApi, apiClient, api envelope types)
      "@shared": fileURLToPath(new URL("./resources/js/shared", import.meta.url)),
      // Core module frontend code, one alias per module
      "@core/auth": fileURLToPath(new URL("./app/Core/Auth/resources/js", import.meta.url)),
      "@core/users": fileURLToPath(new URL("./app/Core/Users/resources/js", import.meta.url)),
      "@core/roles": fileURLToPath(new URL("./app/Core/Roles/resources/js", import.meta.url)),
      "@core/settings": fileURLToPath(new URL("./app/Core/Settings/resources/js", import.meta.url)),
      // Extension frontend code, one alias per extension
      "@extensions/gallery": fileURLToPath(new URL("./app/Extensions/Gallery/resources/js", import.meta.url)),
    },
  },
  server: {
    host: "0.0.0.0",
    port: 5173,
    strictPort: true,

    watch: {
      ignored: ["**/storage/framework/views/**"],
    },
  },
  test: {
    environment: "happy-dom",
    globals: true,
    include: [
      "resources/js/**/*.test.ts",
      "app/**/resources/js/**/*.test.ts",
    ],
  },
});
