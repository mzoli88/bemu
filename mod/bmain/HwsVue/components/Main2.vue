<template>
  <div v-if="forcePWChange" class="forcePWChange cflex">
    <Panel border title="Lejárt a jelszó" size="600px" frame>
      <Form store="admin.password" save style="min-height: 150px" saveOkText="Sikeres jelszó módosítás!"
        :afterSave="afterPasswordChange">
        <Field label="Email" type="text" name="email" v-show="false" autocomplete="username"
          :startValue="getUserData().email" />
        <Field label="Új jelszó" type="password" name="password" required autocomplete="new-password" />
        <Field label="Új jelszó megerősítése" type="password" name="password_confirmation"
          autocomplete="password_confirmation" required />
      </Form>
    </Panel>
  </div>
  <div class="MainTopToolbar toolbar noselect">
    <div class="cflex site_name">
      <a href="#start/0">{{ site_name }}</a>
    </div>
    <div class="cflex" v-if="active_entity && entities.length > 1">
      <Field title="Aktív entitás" class="vflex entityCombo" type="combo" noBlank nosearch placeHolder=""
        :startValue="active_entity" :boxes="entities" @change="setEntity" noLabel>
        <div class="icon cflex">&#xf0e8;</div>
      </Field>
    </div>
    <div class="fit cflex">
      <div v-if="active_modul && buttons && buttons[active_modul]" class="cflex gap top-modul-data">
        <div v-if="site_company" class="site_company cflex">
          {{ site_company }}
        </div>
        <span>
          <Icon>{{ buttons[active_modul].icon }}</Icon>
        </span>
        <span class="top-modul-name">
          {{ buttons[active_modul].name }}
        </span>
        <span class="top-modul-version" v-if="buttons[active_modul].version">
          v{{ buttons[active_modul].version }}
        </span>
        <span v-if="active_menu && buttons[active_modul].menu[active_menu]">
          -
        </span>
        <span v-if="active_menu && buttons[active_modul].menu[active_menu]">
          {{ buttons[active_modul].menu[active_menu].name }}
        </span>
      </div>
    </div>
    <div class="cflex user_name top-user-data">
      {{ userData.name }}
    </div>
    <div class="cflex NotificationCountParent">
      <Button :icon="NotificationLastRout ? 'f3e5' : 'f0f3'" title="Rendszerüzenetek" noHighlight @click="toNotifications"
        :class="{ read: NotificationCount > 0 }">
        <template #notText>
          <div v-if="NotificationCount > 0" class="NotificationCount cflex">
            {{ NotificationCount }}
          </div>
        </template>
      </Button>
    </div>
    <div class="cflex">
      <Button icon="f042" title="Inverz (sötét) mód" @click="doDarkMode" noHighlight />
    </div>
    <div class="cflex">
      <Button icon="f2f5" title="Kijelentkezés" @click="doLogout" highlight />
    </div>
  </div>
  <div class="header_separator"></div>
  <div class="vflex mainCt fit" v-if="render">
    <div class="hflex leftmenuToolbar noselect" ref="leftmenuToolbar" v-if="buttons" @mouseleave="onMouseleave"
      @mouseenter="onMouseenter">
      <div class="menu1" v-if="menuAnimateOn || !canShowMenu()">
        <template v-for="key in Object.keys(buttons)" :key="'mbtn' + key">
          <div ref="menu1btn" class="hflex animate_btn" :title="buttons[key].name" @click="
            empty(buttons[key].menu) ||
              Object.keys(buttons[key].menu).length == 1
              ? onMenuClick(key)
              : setPressed(key)
          " :class="{ activeModul: pressed == key }">
            <div class="library-icon" v-if="
              !(
                empty(buttons[key].menu) ||
                Object.keys(buttons[key].menu).length == 1
              )
            ">
              <Icon>f152</Icon>
            </div>
            <div class="menu_icon cflex fit">
              <Icon v-if="pressed == key && MajaxManager.loading" icon="f013" spin />
              <Icon v-else>{{ buttons[key].icon }}</Icon>
            </div>
            <div class="modulName fit">
              {{ buttons[key].name }}
            </div>
          </div>
        </template>
      </div>
      <div class="menu2" v-if="!menuAnimateOn" ref="menu2Frame">
        <div v-show="canShowMenu()" class="hflex selectedMenuButton animate_btn" @click="setPressed()">
          <div title="Vissza" class="menu_back cflex">
            <Icon>f3e5</Icon>
          </div>
        </div>
        <template v-for="(item, index) in buttons" :key="'mbtn' + index">
          <template v-if="!empty(item.menu) && Object.keys(item.menu).length != 1">
            <template v-for="(key2, index2) in Object.keys(item.menu)" :key="'mbtn' + index2">
              <div v-if="pressed == index" class="hflex animate_btn" :title="item.menu[key2].name"
                :class="{ activeModul: index + '.' + key2 == active }" @click="onMenuClick(index, key2)">
                <div class="menu_icon cflex fit">
                  <Icon v-if="index + '.' + key2 == active && MajaxManager.loading" icon="f013" spin />
                  <Icon v-else>{{ item.menu[key2].icon }}</Icon>
                </div>
                <div class="modulName fit">
                  {{ item.menu[key2].name }}
                </div>
              </div>
            </template>
          </template>
        </template>
      </div>
    </div>
    <div class="hflex main_wrap fit">
      <div class="toolbar historytoolbar">
        <div v-for="(name, key) in history" :key="key + '_' + active" class="vflex">
          <Button noHighlight noSpin :highlight="key == active" @click="ActiveToMenu(key)">{{ name }}</Button>
          <div class="cflex delHistoryIcon pointer" v-if="key != active" @click="deleteHistory(key)">
            <Icon icon="f55a" />
          </div>
        </div>
      </div>
      <keep-alive>
        <component :is="getMainCmp()"></component>
      </keep-alive>
    </div>
  </div>
</template>

<script>
const Contents = {};
//hasPerm hívás miatt kell a g_userData, g_active_modul, ne reaktív legyen a változó. ha reaktív akkor is frissít amikor modult változtat. (false -lesz a jog) 
var g_userData, g_active_modul;

var Mods;
export default {
  name: "Main",
  data: function () {
    document.title = "Kezdőlap";

    var active = this.getCpHash();
    if (!active) this.setHash("start");

    return {
      render: false,
      active: active || "start",
      buttons: null,
      perms: {},
      userData: {},
      entities: {},
      active_entity: parseInt(sessionStorage.getItem("active_entity")) || null,
      site_name: config.name,
      site_company: config.company,
      pressed: null,
      active_modul: null,
      active_menu: null,
      history: { start: "Kezdőlap" },
      menuAnimateOn: false,
      NotificationCount: null,
      NotificationLastRout: null,
      MajaxManager: MajaxManager,
      forcePWChange: false,
    };
  },

  created: function () {
    global.getActiveModul = this.getActiveModul;
    global.getActiveEntity = this.getActiveEntity;
    global.hasPerm = this.hasPerm;
    global.getJog = this.hasPerm;
    global.getUserData = this.getUserData;
    global.isSysAdmin = this.isSysAdmin;
    global.getActiveMenu = this.getActiveMenu;
    global.setNotificationCount = this.setNotificationCount;
    global.resetNotificationLastRout = this.resetNotificationLastRout;

    getStore("admin.menu").load((s, x, code) => {
      if (!s) {
        maskOff();
        if (code == 401) return;
        return msg(x.message, "error");
      }
      this.forcePWChange = !!x.pw_change;
      g_userData = x.userData;
      this.userData = x.userData;
      this.entities = x.entities;
      this.active_entity = this.active_entity || x.active_entity;
      this.perms = x.perms;
      Mods = x.mods;
      // dd(Mods[this.active_entity]);
      this.buttons = Mods[this.active_entity];
      this.ActiveToMenu(this.active);
      this.setTitle();
      this.render = true;
      maskOff();
    });
  },
  watch: {
    active: function (value) {
      global.MajaxManager.deleteAll();

      this.setHash(value);

      this.setTitle();
    },
  },

  methods: {
    ActiveToMenu: function (active) {
      if (!isString(active)) return;
      if (!this.buttons) return;
      if (this.buttons.hasOwnProperty(active)) {
        this.openMenu(active);
      } else {
        active = active.split(".");
        this.openMenu(active[0], active.length == 2 ? active[1] : null);
      }
    },

    setTitle: function () {
      document.title = this.history[this.active];
    },

    onMenuClick: function (modul_azon, modul_menu) {
      if (MajaxManager.loading) return;
      this.openMenu(modul_azon, modul_menu);
    },

    openMenu: function (modul_azon, modul_menu) {
      if (!this.buttons) return;
      clearTimeout(this.tmpTimeout);

      //ha nincs joga a menüponthoz, pl entitás változtatásakor
      if (!this.buttons[modul_azon] || (modul_menu && !(modul_menu && this.buttons[modul_azon].menu && this.buttons[modul_azon].menu[modul_menu]))) {
        g_active_modul = "start";
        this.active_modul = "start";
        this.pressed = "start";
        this.active_menu = null;
        this.active = "start";
        if (!Contents["start"]) {
          Contents["start"] = cLoad("main", "start");
        }
        this.setHash("start");
        return;
      }

      //ha csak egy menüpont van
      if (
        empty(modul_menu) &&
        this.buttons[modul_azon] &&
        this.buttons[modul_azon].menu &&
        Object.keys(this.buttons[modul_azon].menu).length == 1
      ) {
        modul_menu = Object.keys(this.buttons[modul_azon].menu).pop();
      }

      if (modul_azon.includes(".")) {
        let tmp = modul_azon.split(".");
        g_active_modul = tmp.shift();
        this.active_modul = g_active_modul;
        this.active_menu = tmp.shift();
      } else {
        g_active_modul = modul_azon;
        this.active_modul = modul_azon;
        this.active_menu = modul_menu;
      }

      this.pressed = modul_azon;

      if (empty(modul_menu)) {
        this.active = modul_azon;
      } else {
        this.active = modul_azon + "." + modul_menu;
      }

      if (!Contents[this.active]) {
        if (empty(this.active_menu)) {
          Contents[this.active] = cLoad("main");
        } else {
          Contents[this.active] = cLoad(
            this.active_menu + "/main-" + this.active_menu
          );
        }
      }

      this.history[this.active] =
        this.buttons[modul_azon].name +
        (modul_menu && this.buttons[modul_azon].menu && this.buttons[modul_azon].menu[modul_menu]
          ? " - " + this.buttons[modul_azon].menu[modul_menu].name
          : "");
    },

    getMainCmp: function () {
      return Contents[this.active];
    },

    setPressed: function (val) {
      this.pressed = val;
      this.onMenuPressed();
    },

    hasPerm: function () {
      for (var i in arguments) {
        if (isArray(arguments[i])) {
          for (var j in arguments[i]) {
            if (hasPerm(arguments[i][j])) return true;
          }
        } else {
          if (arguments[i] == "SysAdmin" && this.isSysAdmin()) {
            return true;
          }
          if (
            this.perms[this.getActiveEntity()] &&
            this.perms[this.getActiveEntity()][this.getActiveModul()]
          ) {
            var perms =
              this.perms[this.getActiveEntity()][this.getActiveModul()];

            if (perms[arguments[i]] === true || perms[arguments[i]] === "I") {
              return true;
            }
          }
        }
      }
      return false;
    },

    isSysAdmin: function () {
      return g_userData.sys_admin == "I";
    },

    getUserData: function () {
      return g_userData;
    },

    getActiveModul: function () {
      return g_active_modul;
    },

    getActiveEntity: function () {
      return this.active_entity;
    },

    getActiveMenu: function () {
      return this.active_menu;
    },

    setNotificationCount: function (value, noNotification) {
      if (
        !noNotification &&
        !empty(this.NotificationCount) &&
        value > 0 &&
        value > this.NotificationCount
      ) {
        tMsg.i("Új rendszerüzenet érkezett!");
      }

      this.NotificationCount = value;
    },

    resetNotificationLastRout: function () {
      this.NotificationLastRout = null;
    },

    toNotifications: function () {
      if (empty(this.NotificationLastRout)) {
        this.NotificationLastRout = "#" + this.$getHash().join("/");
        setHash("#start/1/list");
      } else {
        setHash(this.NotificationLastRout);
        resetNotificationLastRout();
      }
    },

    setEntity: function (val) {
      if (this.active_entity == val) return;
      this.active_entity = val;
      sessionStorage.setItem("active_entity", val);
      this.render = false;
      this.$nextTick(() => {
        this.buttons = Mods[this.active_entity];
        this.ActiveToMenu(this.active);
        this.history = { start: "Kezdőlap" };
        this.$nextTick(() => {
          this.render = true;
        });
      });
    },

    doDarkMode: function () {
      this.$root.DarkMode = !this.$root.DarkMode;
    },

    doLogout: function () {
      tokenHandler.logout();
    },

    onMouseenter: function () {
      clearTimeout(this.tmpTimeout);
    },

    onMouseleave: function () {
      var oldPressed = this.pressed;
      clearTimeout(this.tmpTimeout);
      this.tmpTimeout = setTimeout((x) => {
        this.ActiveToMenu(this.active);
        // dd (oldPressed , this.pressed);
        if (oldPressed != this.pressed) this.onMenuPressed();
      }, 6000);
    },

    onMenuPressed: function () {
      this.menuAnimateOn = true;
      this.$nextTick(() => {
        if (this.canShowMenu()) {
          this.moveMenu(0, -100, true, () => {
            this.menuAnimateOn = false;
            this.$nextTick(() => {
              fadeIn(this.$refs.menu2Frame, 20);
            });
          });
        } else {
          this.moveMenu(-100, 0, false, () => {
            this.menuAnimateOn = false;
          });
        }
      });
    },

    deleteHistory: function (key) {
      delete this.history[key];
    },

    canShowMenu: function () {
      return (
        this.pressed &&
        this.buttons[this.pressed] &&
        this.buttons[this.pressed].menu &&
        Object.keys(this.buttons[this.pressed].menu).length != 1
      );
    },

    moveMenu: function (from, to, reverse, cb) {
      var buttons = this.$refs.menu1btn.slice();
      if (reverse) buttons = buttons.reverse();
      buttons.forEach((x, n) => {
        setTimeout(() => {
          moveX(
            x,
            to,
            10,
            () => {
              if (cb && n + 1 == this.$refs.menu1btn.length) cb();
            },
            from
          );
        }, 50 * n);
      });
    },

    afterPasswordChange: function () {
      this.forcePWChange = false;
    },

    $hash: function (hash) {
      this.ActiveToMenu(hash);
    },
  },
};
</script>

<style>
.site_name a {
  font-size: 32px;
  animation: lights 20s 10s linear infinite;
  text-decoration: none;
}

.MainTopToolbar {
  position: relative;
  background-color: #fff;
  height: 50px;
  padding: 0 10px;
  color: var(--higlight-text-color);
  gap: 10px;
  z-index: 10;
}

.MainTopToolbar .icon {
  font-size: 22px;
  padding: 0 4px;
}

.header_separator {
  border-top: 2px solid var(--higlight-text-color);
  box-shadow: 0 0px 15px rgb(0 0 0 / 50%);
}

.leftmenuToolbar {
  color: #444;
  box-shadow: 0px 0px 6px rgb(0 0 0 / 50%);
  margin-right: 10px;
  background-color: #fff;
  position: relative;
  min-width: 65px;
}

.leftmenuToolbar>div {
  cursor: pointer;
}

.modulName {
  font-size: 10px;
  padding: 0 3px;
  text-align: center;
  color: #222;
  word-wrap: break-word;
  -webkit-hyphens: auto;
  -moz-hyphens: auto;
  -ms-hyphens: auto;
  -o-hyphens: auto;
  hyphens: auto;
}

.menu_icon .icon {
  font-size: 26px;
}

.animate_btn .menu_icon .icon {
  transition: all 0.2s;
}

.animate_btn:hover .menu_icon .icon {
  /* font-size: 30px; */
  transform: translateX(8px);
}

.delHistoryIcon {
  display: none;
  color: rgb(88, 6, 6);
}

.historytoolbar>div:hover .delHistoryIcon {
  display: inherit;
}

.menu1 {
  flex: 1 1 auto;
  overflow: auto;
}

.menu2 {
  position: absolute;
  top: 0;
  overflow: auto;
  height: calc(100vh - 52px);
}

.animate_btn {
  position: relative;
  /*height: 60px;*/
  padding: 7px 0;
  width: 65px;
}

.selectedMenuButton {
  color: var(--higlight-text-color);
  margin-top: 0px;
  border-bottom: 2px solid var(--higlight-text-color);
  position: relative;
  height: initial;
}

.library-icon .icon {
  position: absolute;
  right: 0px;
  font-size: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--higlight-text-color);
}

.activeModul {
  background-color: #ccc;
  color: var(--higlight-text-color);
}

.historytoolbar .ButtonText {
  /* font-size: calc(10px + var(--main-size)); */
  font-size: 12px !important;
  font-weight: initial;
}

.historytoolbar {
  margin-bottom: 10px;
}

.main_wrap {
  padding-top: 10px;
  padding-left: 10px;
}

.collapsedTopPanelIcon {
  margin-bottom: 6px;
  margin-top: 8px;
}

.vPanel.collapsible.collapsed>.titleBorder {
  flex: 1 1 auto;
  padding: 10px 0;
}

.NotificationCountParent {
  position: relative;
}

.NotificationCountParent .Button.read {
  animation: alert-button-effect 4s infinite;
}

.NotificationCountParent .Button.read .icon {
  animation: ring 4s infinite;
}

.NotificationCount {
  background-color: var(--higlight-text-color);
  color: white;
  padding: 4px;
  border-radius: 100px;
  font-size: 12px;
  min-width: 14px;
  font-weight: bold;
}

.forcePWChange {
  position: absolute;
  width: 100%;
  height: 100%;
  background-color: #fff;
  z-index: 50;
}

@keyframes lights {
  0% {
    text-shadow: -10px 5px 2px #33333300;
  }

  5% {
    text-shadow: 0px 5px 2px #333333dd;
  }

  10% {
    text-shadow: 15px 5px 2px #33333300;
  }
}

@keyframes alert-button-effect {
  0% {
    box-shadow: 0 0 0 0 var(--higlight-text-color);
  }

  15% {
    box-shadow: 0 0 2px 5px rgba(254, 57, 5, 0);
  }
}

@keyframes ring {
  0% {
    transform: rotate(0deg);
  }

  5% {
    transform: rotate(0deg);
  }

  15% {
    transform: rotate(0deg);
  }

  25% {
    transform: rotate(20deg);
  }

  35% {
    transform: rotate(-15deg);
  }

  45% {
    transform: rotate(10deg);
  }

  55% {
    transform: rotate(-5deg);
  }

  60% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(0deg);
  }
}
</style>