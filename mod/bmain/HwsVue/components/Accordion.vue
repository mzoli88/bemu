<template>
  <div class="Accordion hflex fit">
    <slot />
  </div>
</template>


<script>
export default {
  name: "Accordion",

  props: {
    defaultActive: String,
  },

  methods: {
    onPanelCollapseChange: function (panel, collapsed) {
      this.refreshTabs(true);
    },

    $hash: function (hash) {
      if (empty(hash)) {
        this.active = {};
        return;
      }
      var hay = hash.split(",");
      for (var i in this.active) {
        this.active[i] = hay.includes(i);
      }
    },

    refreshTabs: function (set) {
      var me = this;
      const sorthelper = this.$slots
        .default()
        .filter(function (r) {
          return isObject(r.props);
        })
        .map(function (r) {
          return r.props.title;
        });
      this.children
        .sort(function (a, b) {
          let indexA = sorthelper.indexOf(a.title);
          let indexB = sorthelper.indexOf(b.title);
          return indexA - indexB;
        })
        .forEach(function (tab, i) {
          tab.doCollapsible = true;
          tab.collapseDirection = "up";
          if (me.active.hasOwnProperty(i)) {
            if (set) {
              me.active[i] = !tab.isCollapsed;
            } else {
              tab.isCollapsed = !me.active[i];
            }
          } else {
            me.active[i] = tab.opened ? true : false;
            tab.isCollapsed = tab.opened ? false : true;
          }
        });
      //   dd(this.active);
    },
  },

  watch: {
    active: {
      handler: function (val) {
        //végén kell hash beállítás különben nem fog rendesen működni
        var hash = [];
        for (var i in val) {
          if (val[i]) hash.push(i);
        }
        this.setHash(hash.join(","));
        this.refreshTabs();
      },
      immediate: true,
      deep: true,
    },
  },

  mounted: function () {
    this.refreshTabs();
  },

  data: function () {
    var activeString = this.defaultActive || this.getCpHash();
    var active = {};
    if (!empty(activeString)) {
      activeString.split(",").forEach((x) => (active[x] = true));
    }

    return {
      active: active,
    };
  },
};
</script>

<style>
.Accordion {
  gap: 10px;
  /* overflow: hidden; */
  flex-shrink: 0;
  flex-grow: 1;
  flex-basis: auto;
}

.Accordion > .Panel.notCollapsed {
  /* min-height: 500px; */
  flex: 1 0 auto !important;
}

.Accordion > .Panel.collapsible.collapsed > .Title {
  border: 1px solid #ddd;
  border-radius: 4px;
}

.Accordion > .Panel > .titleBorder {
  border: 1px solid #ddd;
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
}
.Accordion > .Panel.collapsed > .titleBorder {
  background-color: #fff;
  color: var(--higlight-text-color);
}

.Accordion > .Panel > .Body {
  background-color: #fff;
  background-color: #fff;
  border-bottom: 1px solid #ddd;
  border-left: 1px solid #ddd;
  border-right: 1px solid #ddd;
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
  padding: 10px;
  /* flex: 1 0 auto; */
  min-height: 400px;
}
</style>
