<template>
  <div class="PopupMain cflex" :class="type" v-show="active">
    <div class="PopupBackground"></div>
    <div class="PopupBody cflex">
      <div class="hflex border fit">
        <div class="Title noselect titleBorder">
          <div class="titletext">{{ header }}</div>
        </div>
        <div class="Body hflex fit">
          <div class="cflex maintenanceSpin fit" v-if="type == 'maintenance'">
            <div class="spin">
              <Icon>f51f</Icon>
            </div>
          </div>
          <div v-show="pre" class="pre msqgPre">{{ pre }}</div>
          <div class="cflex">
            <div v-show="type == 'info' || type == 'error' || type == 'warn'">
              <Button @click="onOk" ref="okBtn">Rendben</Button>
            </div>
            <div v-show="type == 'i/n'" class="toolbar cflex">
              <Button @click="onYesNo(true)">Igen</Button>
              <Button @click="onYesNo(false)" highlight>Nem</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Mask from "./Mask.vue";

export default {
  created: function () {
    var me = this;
    global.msg = function (txt, type, fn, pre) {
      txt = txt || '';
      if (me.type == "maintenance" && type == "off") {
        me.active = false;
        return;
      }
      if (type == "off") return;
      txt = txt.split("|");
      me.active = true;
      me.header = txt.shift().trim();
      type = type || "info";
      me.type = type;
      me.callback = fn;
      me.pre = pre || txt.pop();
      me.$nextTick(function () {
        document.activeElement.blur();
        me.$refs.okBtn.$el.focus();
      });
    };
    global.ask = function (txt, fn, pre) {
      msg(
        txt,
        "i/n",
        (s) => {
          if (s) fn();
        },
        pre
      );
    };
  },
  data: function () {
    return {
      callback: null,
      active: false,
      header: "Siker",
      type: "ok",
      pre: null,
    };
  },
  methods: {
    onOk: function () {
      if (this.callback) this.callback();
      this.active = false;
    },
    onYesNo: function (s) {
      if (this.callback) this.callback(s);
      this.active = false;
    },
  },
  components: {
    Mask,
  },
};
</script>

<style>
.PopupMain {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 2000;
}

.PopupBody > .Panel {
  width: 400px;
}

.PopupBackground {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0.6;
  background-color: #fff;
}

.stop .PopupBackground,
.maintenance .PopupBackground {
  opacity: 1;
}

.maintenance .titletext,
.maintenance .msqgPre {
  font-size: 24px;
}
.maintenance .maintenanceSpin {
  margin: 10px;
}
.maintenance .maintenanceSpin .icon {
  font-size: 34px;
  color: var(--higlight-text-color);
}

.PopupBody {
  opacity: 1;
  z-index: 2100;
  max-width: 600px;
}
.PopupBody > .border > .Body {
  padding: 5px;
  text-align: center;
}

.PopupBody .titleBorder {
  height: auto;
}

.PopupBody .titletext {
  white-space: -moz-pre-wrap;
  white-space: -pre-wrap;
  white-space: -o-pre-wrap;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.msqgPre {
  padding: 5px;
}

.PopupMain .PopupBody {
  box-shadow: 0px 0px 4px 4px #ddd;
}

.PopupMain .titleBorder {
  text-align: center;
  font-size: 16px;
  font-weight: bold;
  background-color: var(--title-background-color);
  color: var(--title-color);
  padding: 15px;
}

.PopupMain.error .PopupBody {
  box-shadow: 0px 0px 8px 2px #a94442;
}

.PopupMain.error .Button,
.PopupMain.error .border,
.PopupMain.error .titleBorder {
  color: #a94442;
  border-color: #ebccd1;
  border-width: 1px;
}

.PopupMain.error .Button,
.PopupMain.error .titleBorder {
  background-color: #f2dede;
}

.PopupMain.error .Button:focus {
  border: 1px solid #ebccd1;
}

.PopupMain.info .PopupBody {
  box-shadow: 0px 0px 8px 2px #3c763d;
}

.PopupMain.info .Button,
.PopupMain.info .border,
.PopupMain.info .titleBorder {
  color: #3c763d;
  border-color: #d6e9c6;
  border-width: 1px;
}

.PopupMain.info .Button,
.PopupMain.info .titleBorder {
  background-color: #dff0d8;
}

.PopupMain.info .Button:focus {
  border: 1px solid #d6e9c6;
}

.PopupMain .Body {
  background-color: #fff;
}
</style>