import "./../mainStyle.css";

import { defineAsyncComponent } from 'vue'

import "../helpers/Dd"
import "../helpers/isType"
import "../helpers/Majax"
import "../helpers/Validators"
import "../helpers/getConfig"
import Hash from "../helpers/Hash";
import GetComp from "../helpers/getComp";
import Formh from "../helpers/Form";
import Panel from "./../components/Panel.vue";
import Form from "./../components/Form.vue";
import Tab from "./../components/Tab.vue";
import Icon from "./../components/Icon.vue";
import Button from "./../components/Button.vue";
import Pager from "./../components/Pager.vue";

const Grid = defineAsyncComponent(() =>
  import('./../components/grid/Grid.vue')
)
import Dropdown from "./../components/Dropdown.vue";
import Field from "./../components/Field.vue";
import Globals from "../helpers/globals";
import BorderLogin from "./../components/BorderLogin.vue";
import Accordion from "./../components/Accordion.vue";


//jó megjelenítés miatt modult később szabad betölteni
import AppCmp from "./../components/App.vue";
import { createApp } from 'vue'
const App = createApp(AppCmp);
App.config.unwrapInjectedRef = true;

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
App.component("Dropdown", Dropdown);
App.component("Field", Field);
App.component("Accordion", Accordion);

global.On_401 = function () {
    App.unmount();
    const Login = createApp(BorderLogin);
    Login.use(Globals);
    Login.component("Button", Button);
    Login.component("Icon", Icon);
    Login.mount("#app");
};

App.mount("#app");

