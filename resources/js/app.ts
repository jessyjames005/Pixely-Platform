import { createApp } from "vue";
import { createPinia } from "pinia";
import "@mdi/font/css/materialdesignicons.css";

import SwaggerUIBundle from "swagger-ui-dist/swagger-ui-bundle.js";
import SwaggerUIStandalonePreset from "swagger-ui-dist/swagger-ui-standalone-preset.js";

import App from "./App.vue";
import router from "./router";
import { vuetify } from "./shared/plugins/vuetify";
import { i18nPlugin } from "./shared/plugins/i18n";

window.SwaggerUIBundle = SwaggerUIBundle;
window.SwaggerUIStandalonePreset = SwaggerUIStandalonePreset;

const appElement = document.getElementById("app");

if (appElement) {
  createApp(App).use(createPinia()).use(router).use(vuetify).use(i18nPlugin).mount(appElement);
}
