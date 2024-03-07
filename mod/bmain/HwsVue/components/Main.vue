<template>
  <Panel
    v-show="show"
    v-if="hasMenu"
    class="Menu"
    h
    border
    collapsible
    :collapsed="menuCollapsed"
    size="300px"
  >
    <template #titlecollapsed>
      <div class="pictoMenu vflex toolbar">
        <template v-for="(item, index) in buttons" :key="'mbtn' + index">
          <Button
            :active="active == index"
            @click="click(index)"
            :title="item"
            :icon="icons[index]"
            class="collapsedMenuButton cflex"
          />
        </template>
      </div>
    </template>
    <div class="Nevjegy">
      <div class="nevjegy-name">
        {{ userData.modul_nev }}
      </div>
      <div class="nevjegy-version">
        {{ userData.modul_verzio }}
      </div>
      <div class="nevjegy-owner">{{ userData.modul_company }}</div>
    </div>
    <template v-for="(item, index) in buttons" :key="'m2btn' + index">
      <Button
        class="MenuButton cflex"
        :active="active == index"
        @click="click(index)"
        :icon="icons[index]"
        >{{ item }}</Button
      >
    </template>
  </Panel>
  <div class="hflex fit">
    <div v-if="web || $root.queque" class="toolbar MainTopToolbar">
      <template v-if="$root.queque_data">
        <span></span>
        <Button
          v-if="$root.queque_data.majax_type == 'export'"
          icon="f013"
          spin
        >
          Exportálás folyamatban
          <span v-if="$root.queque_signal">{{ $root.queque_signal }}</span>
        </Button>
        <Button
          v-if="$root.queque_data.majax_type == 'export'"
          title="Export leállítása"
          class="stopExportBtn"
          icon="Mégsem"
          @click="doStopExport"
        />
        <Button
          v-if="$root.queque_data.majax_type == 'import'"
          icon="f013"
          spin
        >
          Importálás folyamatban
          <span v-if="$root.queque_signal">{{ $root.queque_signal }}</span>
        </Button>
        <Button
          v-if="$root.queque_data.majax_type == 'dowloadexport'"
          icon="f019"
          class="downloadReadyButton"
          @click="doDownloadExport"
        >
          Export fájl letöltése
        </Button>
        <Button
          v-if="$root.queque_data.majax_type == 'dowloadimport'"
          icon="f019"
          class="downloadReadyButtonImport"
          @click="doDownloadImport"
        >
          Import hibák letöltése
        </Button>
      </template>
      <div v-if="web" class="fit"></div>
      <div v-if="web" class="nevjegy-user cflex">{{ userData.teljesnev }}</div>
      <Button
        v-if="web"
        icon="f2f5"
        title="Kijelentkezés"
        @click="doLogout"
        highlight
      />
    </div>
    <keep-alive>
      <component
        :is="active"
      ></component>
    </keep-alive>
  </div>
</template>

<script>
import Params from "./../../src/main";
const cmp = {};
const buttons = {};
const icons = {};
const jogok = {};

global.config = {
  remote_url: "..",
};

for (var i in Params) {
  cmp[i] = Params[i].render;
  buttons[i] = Params[i].text;
  icons[i] = Params[i].icon;
  jogok[i] = Params[i].permissions;
}

export default {
  name: "Main",
  components: cmp,
  props: {
    web: Boolean,
  },
  data: function name() {
    return {
      show: true,
      active: null,
      buttons: {},
      icons: {},
      jogok: jogok,
      userData: {},
      menuCollapsed: true,
      hasMenu: true,
    };
  },
  created: function () {
    var me = this;
    global.colapseMenu = function (colapse) {
      me.menuCollapsed = colapse ? true : false;
    };

    global.getActiveMenu = function () {
      return me.active;
    };

    global.menu = {
      hide: function () {
        me.hasMenu = false;
      },
      show: function () {
        me.hasMenu = true;
      },
    };

    global.getJog = me.getJog;
    global.getUserData = me.getUserData;

    var me = this,
      jogStore = getStore("permissions");
    if (!jogStore) throw 'No "permissions" store defined!';
    jogStore.onLoad(function (s, data, code) {
      maskOff();
      if (!s) return; // me.errScreen(me.ErrText(code, data));
      me.userData = data;
      me.$root.entity = data.entity_data;

      //sorbanállás betöltése
      if (
        data.CacheQueue &&
        data.CacheQueue.majax_type &&
        data.CacheQueue.majax_store
      ) {
        setQueque(
          data.CacheQueue.majax_type,
          data.CacheQueue.majax_store,
          data.CacheQueue
        );
      }
    }, me);
    jogStore.load(function (s, data, code) {
      if (!s) return; // me.errScreen(me.ErrText(code, data));

      for (let i in jogok) {
        if (getJog(jogok[i]) || jogok[i] === true) {
          me.buttons[i] = buttons[i];
          me.icons[i] = icons[i];
          length++;
        }
      }
      if (length == 0) return me.errScreen(me.ErrText(403));
      me.setActive();
      me.up("App").startHash();
    });
  },
  watch: {
    active: function (index) {
      //végén kell hash beállítás különben nem fog rendesen működni
      if (index) this.setHash(index + (this.show ? "" : "|hide"));
    },
  },

  methods: {
    click: function (index) {
      this.setActive(index);
    },

    setActive: function (index) {
      if (!index || !this.buttons[index]) {
        if (this.active) return false;
        let hash = this.getCpHash();
        if (hash) {
          index = hash.split("|").shift();
        } else {
          index = Object.keys(this.buttons)[0];
        }
      }
      if (!this.buttons[index]) index = Object.keys(this.buttons)[0];
      this.active = index;
    },

    errScreen: function (text) {
      document.body.className = "cflex errScreen";
      document.body.innerHTML =
        '<div class="InnerErrScreen fa">' + text + "</div>";
    },

    ErrText: function (code, res) {
      switch (code) {
        case 401:
          return "Nincs belépve!";
        case 403:
          return "Nincs jogosultság!";
        default:
          let txt = false;
          if (res && isString(res.error)) txt = res.error;
          if (res && res.data && isString(res.data.message))
            txt = res.data.message;
          if (res && isString(res.message)) txt = res.message;
          if (txt === false) txt = "Szerver oldali hiba!";
          return txt;
      }
    },

    getJog: function () {
      for (var i in arguments) {
        if (isArray(arguments[i])) {
          if (this.getJog.apply(this, arguments[i])) return true;
        } else {
          if (
            this.userData.permissions[arguments[i]] === true ||
            this.userData.permissions[arguments[i]] === "I"
          ) {
            return true;
          }
        }
      }
      return false;
    },

    getUserData: function () {
      return this.userData;
    },

    doLogout: function () {
      quequeOff();
      tokenHandler.logout();
    },

    doDownloadExport: function () {
      setQueque("downloadExport", this.$root.queque_data.majax_store);
    },
    doDownloadImport: function () {
      setQueque("downloadImport", this.$root.queque_data.majax_store);
    },
    doStopExport: function () {
      setQueque("stopExport", this.$root.queque_data.majax_store);
    },

    $hash: function (hash) {
      var active = null;
      if (!empty(hash)) {
        var tmp = hash.split("|");
        if (tmp.indexOf("hide") >= 0) {
          this.show = false;
        }
        if (isNumeric(tmp[1])) {
          //Entitást állítjuk át
          this.$root.changeEntity(tmp[1]);
        }
        if (tmp.length) active = tmp[0];
      }

      this.setActive(active);
    },
  },
};
</script>

<style>
.Menu {
  margin-right: 15px;
}

.MainTopToolbar {
  /* background-color: #1e5999; */
  padding: 6px;
  position: absolute;
  top: 3px;
  right: 2px;
  border-radius: 4px;
  z-index: 10;
  color: white;
}

.MainTopToolbar .nevjegy-user {
  margin-right: 16px;
}

.collapsedTopPanelIcon {
  height: 51px;
}

.downloadReadyButton {
  background-color: #97e797;
  color: #000;
  border-color: #aaa;
}

.downloadReadyButtonImport,
.stopExportBtn {
  background-color: #ff7979;
  color: #000;
  border-color: #ff7979;
}
</style>