
import "./extra.css";
import "./../mainStyle.css";
import "./../assets/favicon.ico";
import "./../assets/fa-solid-900.woff2";
import "./../assets/fa-solid-900.ttf";
import "./../assets/fa-regular-400.woff2";
import "./../assets/fa-regular-400.ttf";

import "./../helpers/Dd"
import "./../helpers/SWorker"
import "./../helpers/Majax"
import "./../helpers/cLoader"
import "./../helpers/isType"
global.defaultToken = true;
import "./../helpers/Validators"
import "./../helpers/getConfig"
import "./../helpers/animations"
import "./../helpers/Queque"
import Hash from "./../helpers/Hash"
import GetComp from "./../helpers/getComp"
import Formh from "./../helpers/Form"
import Panel from "./../components/Panel.vue"
import Form from "./../components/Form.vue"
import Tab from "./../components/Tab.vue"
import Icon from "./../components/Icon.vue"
import Button from "./../components/Button.vue"
import Pager from "./../components/Pager.vue"
import Grid from "./../components/grid/Grid.vue"
import Detail from "./../components/grid/Detail.vue"
import Dropdown from "./../components/Dropdown.vue"
import Field from "./../components/Field.vue"
import Globals from "./../helpers/globals"
import Login from "./../components/Login.vue"
import Accordion from "./../components/Accordion.vue"
import AppCmp from "./../components/App2.vue"
import "vue/dist/vue.esm-bundler"
import { createApp } from 'vue'

const App = createApp(AppCmp);

// App.config.unwrapInjectedRef = true;

App.use(Hash);
App.use(GetComp);
App.mixin(GetComp.childrenmixin);
App.use(Formh);
App.use(Globals);

App.component("Panel", Panel);
App.component("Form", Form);
App.component("Tab", Tab);
App.component("Icon", Icon);
App.component("Button", Button);
App.component("Pager", Pager);
App.component("Grid", Grid);
App.component("Detail", Detail);
App.component("Dropdown", Dropdown);
App.component("Field", Field);
App.component("Login", Login);
App.component("Accordion", Accordion);

App.mount("#app");
