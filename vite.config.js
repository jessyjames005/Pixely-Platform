import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import vue from "@vitejs/plugin-vue";
import vuetify from "vite-plugin-vuetify";

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
    vuetify({ autoImport: true }),
  ],
  resolve: {
    alias: {
      "@shared": fileURLToPath(
        new URL("./resources/js/shared", import.meta.url),
      ),
      "@core/auth": fileURLToPath(
        new URL("./app/Core/Auth/resources/js", import.meta.url),
      ),
      "@core/users": fileURLToPath(
        new URL("./app/Core/Users/resources/js", import.meta.url),
      ),
      "@core/roles": fileURLToPath(
        new URL("./app/Core/Roles/resources/js", import.meta.url),
      ),
      "@core/settings": fileURLToPath(
        new URL("./app/Core/Settings/resources/js", import.meta.url),
      ),
      "@extensions/gallery": fileURLToPath(
        new URL("./app/Extensions/Gallery/resources/js", import.meta.url),
      ),
      "@core/extensions": fileURLToPath(
        new URL("./app/Core/Extensions/resources/js", import.meta.url),
      ),
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
    include: ["resources/js/**/*.test.ts", "app/**/resources/js/**/*.test.ts"],
  },
});
