import { createApp } from "vue";

import SwaggerUIBundle from "swagger-ui-dist/swagger-ui-bundle.js";
import SwaggerUIStandalonePreset from "swagger-ui-dist/swagger-ui-standalone-preset.js";

import App from "./App.vue";
import router from "./router";

/**
 * Expose Swagger UI constructors globally.
 *
 * The Swagger UI Blade view uses these objects to initialise
 * the interactive API documentation.
 */
window.SwaggerUIBundle = SwaggerUIBundle;
window.SwaggerUIStandalonePreset = SwaggerUIStandalonePreset;

/**
 * Bootstrap the Vue application.
 *
 * The application is mounted only when the Vue root element
 * exists on the current page.
 *
 * This allows the same Vite entry point to coexist with
 * Laravel pages such as the Swagger UI documentation.
 */
const appElement = document.getElementById("app");

if (appElement) {
  createApp(App).use(router).mount(appElement);
}
