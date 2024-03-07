<template>
  <Mask :class="{ DarkMode: DarkMode }" :show="isloading" />

  <MainMsg></MainMsg>

  <Popup :class="{ DarkMode: DarkMode }"></Popup>

  <div id="app" class="hflex" v-if="rendered" :class="{ DarkMode: DarkMode }">
    <Main ref="main" v-if="Auth.logged" />
    <Web ref="web" v-else />
  </div>
</template>

<script>
import Popup from "./Popup.vue";
import Main from "./Main2.vue";
import Web from "./Web2.vue";
import Mask from "./Mask.vue";
import MainMsg from "./Msg.vue";

//lazy load
const TestData = () => import("../TestData.js");

export default {
  name: "App",
  components: { Popup, Main, Web, Mask, MainMsg },

  data: function name() {
    return {
      isloading: true,
      Auth: Auth,
      rendered: false,
      DarkMode: localStorage.getItem("DarkMode") || false,
      entity: null,
      debug: sessionStorage.getItem("debug_on") == "1" ? true : false || false,
      queque: false,
      queque_data: null,
      queque_signal: null,
    };
  },

  watch: {
    DarkMode: function (val) {
      if (val) {
        localStorage.setItem("DarkMode", 1);
      } else {
        localStorage.removeItem("DarkMode");
      }
      SWorker.darkmode(val);
    },
  },

  created: function () {
    var me = this;

    Auth.token = sessionStorage.getItem("WebToken");
    SWorker.register();
    if (Auth.token) {
      Auth.logged = true;
    } else {
      SWorker.getToken();
    }
    SWorker.refresh();

    global.maskOn = function () {
      me.isloading = true;
    };
    global.maskOff = function () {
      me.$nextTick(function () {
        me.isloading = false;
      });
    };

    global.DarkMode = function (on) {
      me.DarkMode = on;
    };

    if (me.debug) TestData();
    global.debug = function () {
      me.debug = !me.debug;
      sessionStorage.setItem("debug_on", me.debug ? "1" : "0");
      if (me.debug) TestData();
      return "debug mode " + (me.debug ? "on" : "off") + "!";
    };

    global.isDebug = function () {
      return me.debug;
    };

    me.changeCssVars(getConfig().smink);
    me.startHash();
    maskOff();
  },

  methods: {
    startHash: function () {
      this.rendered = true;
      this.$startHash();
    },

    getEntitiyName: function (val) {
      val = val || this.$root.entity ? this.$root.entity.active : false;
      let found = this.$root.entity.list
        .filter(function (row) {
          return row.value == val;
        })
        .pop();
      if (found) return found.name;
    },

    changeEntity: function (val, fn) {
      if (this.$root.entity && this.$root.entity.active != val) {
        getStore("permissions").load(
          {
            active_entity_id: val,
          },
          fn
        );
      }
    },

    changeCssVars: function (params) {
      var root = document.querySelector(":root");
      for (var key in params) {
        if (key.includes("-size")) params[key] += "px";
        root.style.setProperty("--" + key, params[key]);
      }
    },
  },
};
</script>