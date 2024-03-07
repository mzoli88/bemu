<template>
  <div class="Tab hflex border" style="flex: 1 1 100%">
    <div class="Tabmenu noselect">
      <template v-for="(title, i) in titles" :key="'tab_' + i">
        <div
          class="titleBorder tabbtn pointer"
          v-on:click="onClick(i)"
          :class="{ active: i == active }"
          :title="bubbles[i]"
        >
          <span>{{ title }}</span>
        </div>
      </template>
      <div class="tabinline titleBorder tabbtn" v-if="hasSlot('tab')">
        <div class="toolbar">
          <slot name="tab" />
        </div>
      </div>
    </div>
    <div class="vflex Body" ref="body" style="flex: 1 1 100%">
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  name: "Tab",

  emits: ["change"],

  methods: {
    setActive: function (index) {
      index = index || 0;
      if (index == this.active) return;
      this.active = index || 0;
    },

    onClick: function (index) {
      if (MajaxManager.loading) return;
      this.setActive(index);
    },

    $hash: function (hash) {
      if (this.tabIds2.hasOwnProperty(hash)) {
        this.setActive(this.tabIds2[hash]);
      } else {
        this.setActive(hash);
      }
    },

    refreshTabs: function (change) {
      const me = this;
      var titles = [];
      var bubbles = [];
      const sorthelper = this.$slots
        .default()
        .filter(function (r) {
          return isObject(r.props) && r.props.title;
        })
        .map(function (r) {
          return r.props.title;
        });
      this.children
        .filter(function (cmp) {
          return cmp.$options.name == "Panel" && sorthelper.includes(cmp.title);
        })
        .sort(function (a, b) {
          let indexA = sorthelper.indexOf(a.title);
          let indexB = sorthelper.indexOf(b.title);
          return indexA - indexB;
        })
        .forEach(function (tab, i) {
          titles.push(tab.title);
          bubbles.push(tab.bubble);

          if (tab.tabId) {
            me.tabIds[i] = tab.tabId;
            me.tabIds2[tab.tabId] = i;
          }

          tab.hidden = me.active != i;

          if (me.active == i) {
            tab.render = true;
            
            if (change) {
              tab.$nextTick(function () {
                var cmpwithevents = this.getComps({
                  tabPanelChange: Function,
                });
                for (var i in cmpwithevents) {
                  cmpwithevents[i].tabPanelChange();
                }
              });
            }
          }
        });

      this.bubbles = bubbles;
      this.titles = titles;
      //ha utolsó tab eltűnik akkor másik legyen az aktív tab
      if (!empty(this.titles) && empty(this.titles[this.active]))
        this.setActive(this.titles.length ? this.titles.length - 1 : 0);
    },
  },

  watch: {
    active: function (val) {
      val = val || 0;
      //végén kell hash beállítás különben nem fog rendesen működni
      this.refreshTabs(true);
      if (this.tabIds.hasOwnProperty(val)) {
        this.setHash(this.tabIds[val]);
      } else {
        this.setHash(val);
      }
      this.$emit("change", val || 0, this);
    },
    children: {
      handler: function (val) {
        if (!this.mountedTab) return;
        this.refreshTabs();
      },
      immediate: true,
      deep: true,
    },
  },

  mounted: function () {
    this.mountedTab = true;
    this.refreshTabs();
    this.firstLoaded = true;
  },

  data: function () {
    var hash = this.getCpHash(),
      active = parseInt(hash) || 0;
    if (!isNumeric(active)) active = 0;
    this.tabIds = {};
    this.tabIds2 = {};
    this.$slots
      .default()
      .filter(function (r) {
        return isObject(r.props) && r.props.title;
      })
      .forEach((tab, i) => {
        if (tab.props.tabId) {
          this.tabIds[i] = tab.props.tabId;
          this.tabIds2[tab.props.tabId] = i;
        }
      });
    if (hash && this.tabIds2.hasOwnProperty(hash)) {
      active = this.tabIds2[hash];
      // this.setHash(hash);
    } else {
      if (this.tabIds.hasOwnProperty(active)) {
        this.setHash(this.tabIds[active]);
      } else {
        this.setHash(active);
      }
    }
    return {
      active: active,
      titles: [],
      bubbles: [],
    };
  },
};
</script>

<style>
.Tab > .Tabmenu > .tabbtn {
  display: inline-block;
}
.Tabmenu {
  margin-bottom: 5px;
}

.Tabmenu > .tabbtn {
  padding: 13px;
  font-weight: bold;
}

.Tabmenu > .tabinline.tabbtn {
  padding: 0;
  font-weight: initial;
  margin-left: 10px;
}

.Tabmenu > .tabbtn {
  border-bottom: 4px solid transparent;
}

.Tabmenu .tabbtn.active {
  border-bottom: 4px solid var(--higlight-text-color);
  color: var(--higlight-text-color);
}
</style>