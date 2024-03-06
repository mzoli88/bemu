<template>
  <Mask :show="isloading" />

  <MainMsg></MainMsg>

  <Popup></Popup>

  <div id="app" class="hflex" v-if="rendered" >
    <Main ref="main" />
  </div>
</template>

<script>
import Popup from "./../HwsVue/components/Popup.vue";
import Mask from "./../HwsVue/components/Mask.vue";
import MainMsg from "./../HwsVue/components/Msg.vue";
import Main from "./Main.vue";

//lazy load
const TestData = () => import("../HwsVue/TestData.js");

export default {
  name: "App",
  components: { Popup, Main, Mask, MainMsg },

  data: function name() {
    return {
      isloading: true,
      rendered: false,
      entity: null,
      debug: sessionStorage.getItem("debug_on") == "1" ? true : false || false,
    };
  },

  created: function () {
    var me = this;

    global.maskOn = function () {
      me.isloading = true;
    };
    global.maskOff = function () {
      me.$nextTick(function () {
        me.isloading = false;
      });
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