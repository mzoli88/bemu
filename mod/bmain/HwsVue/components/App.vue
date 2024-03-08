<template>
  <Mask :show="isloading" />

  <MainMsg></MainMsg>

  <Popup></Popup>

  <div id="app" class="vflex" v-if="rendered">
    <Main ref="main" />
  </div>
</template>

<script>
import Popup from "./Popup.vue";
import Main from "./Main.vue";
import Mask from "./Mask.vue";
import MainMsg from "./Msg.vue";

//lazy load
const TestData = () => import("./../TestData.js");

export default {
  name: "App",
  components: { Popup, Main, Mask, MainMsg },

  data: function name() {
    return {
      isloading: true,
      rendered: false,
      entity: null,
      debug: sessionStorage.getItem("debug_on") == "1" ? true : false || false,
      queque: false,
      queque_data: null,
      queque_signal: null,
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
    global.quequeOn = function (majax_type, majax_store) {
      me.queque_data = {
        majax_type: majax_type,
        majax_store: majax_store,
      };
      me.queque = true;
    };
    global.quequeOff = function () {
      me.queque_data = null;
      me.queque = false;
      me.queque_signal = null;
      clearTimeout(me.timequeque);
    };
    global.setQueque = function (majax_type, majax_store, data) {
      if (data) {
        if (data.signal) me.queque_signal = data.signal;
        if (!data.hasOwnProperty("ready")) return quequeOff();
        if (data.ready == true && majax_type == "stopExport") {
          if (data.content == "error") {
            return quequeOff();
          }
          if (isObject(data.content) && data.content.download) {
            return quequeOn("dowloadexport", majax_store);
          }
          return quequeOff();
        }
        if (data.ready == true && majax_type == "export") {
          if (data.content == "error") {
            msg("Sikertelen exportálás!", "error");
            return quequeOff();
          }
          if (isObject(data.content) && data.content.download) {
            return quequeOn("dowloadexport", majax_store);
          }
          return quequeOff();
        }

        if (data.ready == true && majax_type == "import") {
          if (data.content == "error") {
            msg("Sikertelen importálás!", "error");
            return quequeOff();
          }
          if (isObject(data.content)) {
            if (data.content.success == false) {
              msg(
                "Sikertelen importálás!",
                "error",
                null,
                data.content.message
              );
              return quequeOff();
            }

            msg("Sikertelen importálás!", "error");
            if (data.content.download) {
              return quequeOn("dowloadimport", majax_store);
            } else {
              return quequeOff();
            }
          } else {
            msg("Sikeres importálás!", "info", function () {
              getStore(majax_store).load();
            });
          }
          return quequeOff();
        }
      }

      quequeOn(majax_type, majax_store);

      switch (majax_type) {
        case "export":
          me.timequeque = setTimeout((x) => {
            getStore(majax_store).export(
              { check_ready: 1 },
              function (s, d) {
                setQueque("export", majax_store, d);
              }
            );
          }, 5000);
          break;
        case "import":
          me.timequeque = setTimeout((x) => {
            getStore(majax_store).import(
              { check_ready: 1 },
              function (s, d) {
                setQueque("import", majax_store, d);
              }
            );
          }, 5000);
          break;
        case "downloadExport":
          quequeOff();
          getStore(majax_store).export({ download: 1 }, "newTab");
          break;
        case "stopExport":
          me.timequeque = setTimeout((x) => {
            getStore(majax_store).export(
              { stop: 1 },
              function (s, d) {
                setQueque("stopExport", majax_store, d);
              }
            );
          }, 1000);
          break;
        case "downloadImport":
          quequeOff();
          getStore(majax_store).import({ download: 1 }, "newTab");
          break;
        default:
          break;
      }
    };
    me.rendered = true;
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
  },
};
</script>