<template>
  <div class="vflex mainCt fit" v-if="render">
    <Panel
      v-if="buttons"
      class="Menu"
      h
      border
      collapsible
      collapsed
      size="300px"
    >
      <template #titlecollapsed>
        <div class="pictoMenu vflex toolbar">
          <template v-for="(item, index) in buttons" :key="'mbtn' + index">
            <Button
              :active="active_menu == index"
              @click="onMenuClick(index)"
              :title="item.name"
              :icon="item.icon"
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
          :active="active_menu == index"
          @click="onMenuClick(index)"
          :icon="item.icon"
          >{{ item.name }}</Button
        >
      </template>
    </Panel>
    <div class="hflex main_wrap fit">
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

    var active = this.getCpHash();

    if (!active) msg("Nincs modul kiválasztva!");

    var tmp = active.split(".");

    return {
      render: false,
      active: active,
      buttons: null,
      perms: {},
      userData: {},
      // entities: {},
      // active_entity: parseInt(sessionStorage.getItem("active_entity")) || null,
      // site_name: config.name,
      // site_company: config.company,
      pressed: null,
      active_modul: tmp[0],
      active_menu: tmp.length == 2 ? tmp[1] : null,
      menuAnimateOn: false,
      MajaxManager: MajaxManager,
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

    // this.ActiveToMenu(this.active);

    // dd (this.active,this.active_modul,this.active_menu);

    getStore(this.active_modul +".perms").load({
      modul:this.active_modul
    },(s, x, code) => {
      if (!s) {
        maskOff();
        if (code == 401) return;
        return msg(x.message, "error");
      }

      g_userData = JSON.parse(JSON.stringify(x));
      this.userData = x;
      // this.entities = x.entities;
      // this.active_entity = this.active_entity || x.active_entity;
      this.perms = x.perms;
      // Mods = x.mods;
      // dd(Mods[this.active_entity]);
      // this.buttons = Mods[this.active_entity];
      
      this.buttons = x.menu;
      // dd (this.menu);
      this.ActiveToMenu(this.active);
      // this.setTitle();
      this.render = true;
      maskOff();
    });
  },
  watch: {
    active: function (value) {
      global.MajaxManager.deleteAll();

      this.setHash(value);

      // this.setTitle();
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

    onMenuClick: function (modul_menu) {
      if (MajaxManager.loading) return;
      this.openMenu(this.active_modul, modul_menu);
    },

    openMenu: function (modul_azon, modul_menu) {
      if (!this.buttons) return;
      clearTimeout(this.tmpTimeout);      

      g_active_modul = modul_azon;
      this.active_modul = modul_azon;

      if(empty({modul_menu}) || !this.buttons[modul_menu]){
        modul_menu = Object.keys(this.buttons)[0];
      }

      this.active_menu = modul_menu;
      this.active = modul_azon + "." + modul_menu;

      if (!Contents[this.active]) {
          Contents[this.active] = cLoad(
            this.active_menu + "/main-" + this.active_menu
          );
      }

    },

    getMainCmp: function () {
      return Contents[this.active];
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

    setEntity: function (val) {
      if (this.active_entity == val) return;
      this.active_entity = val;
      sessionStorage.setItem("active_entity", val);
      this.render = false;
      this.$nextTick(() => {
        this.buttons = Mods[this.active_entity];
        this.ActiveToMenu(this.active);
        this.$nextTick(() => {
          this.render = true;
        });
      });
    },

    $hash: function (hash) {
      this.ActiveToMenu(hash);
    },
  },
};
</script>

<style>

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

.main_wrap {
  padding-top: 10px;
  padding-left: 10px;
}

.vPanel.collapsible.collapsed>.titleBorder {
  flex: 1 1 auto;
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