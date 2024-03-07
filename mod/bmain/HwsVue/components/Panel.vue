<template>
  <div
    v-if="render"
    v-show="!hidden"
    class="Panel hflex border"
    :style="panelflex"
    :class="{
      collapsed: isCollapsed,
      notCollapsed: !isCollapsed,
      pointer: isCollapsed && !hasTieleSlot,
      collapsible: doCollapsible,
      notCollapsible: !doCollapsible,
      PanelBorder: border,
      notPanelBorder: !border,
      windowPanel: window,
      vPanel: vPanel,
      hPanel: hPanel,
    }"
    @click="onClick"
  >
    <div
      ref="titleEl"
      v-if="(title || doCollapsible || hasTieleSlot) && noTabParent"
      class="Title noselect titleBorder vflex"
      :class="[
        collapseDirection,
        isAccordion || (doCollapsible && !hasTieleSlot) ? 'pointer' : null,
      ]"
      @click="collapseClick"
    >
      <div
        class="collapsedTopPanelIcon collapsedIconToolbar pointer cflex"
        v-if="doCollapsible && vPanel && isCollapsed"
        ref="collapsedIcon"
      >
        <span class="icon collapsedIcon"></span>
      </div>
      <div
        v-if="isCollapsed && hasSlot('titlecollapsed')"
        class="titlecollapsed vflex"
      >
        <slot name="titlecollapsed" />
      </div>
      <div class="titletext toolbar cflex">
        <div style="position: relative">
          <slot name="title">
            <span>&nbsp;{{ title }}</span>
          </slot>
          <div v-if="hasSlot('titlebuttons')" class="paneltitlebuttons toolbar">
            <slot name="titlebuttons" />
          </div>
        </div>
      </div>
      <div class="fit"></div>
      <div
        class="collapsedIconToolbar pointer cflex"
        v-if="doCollapsible && (hPanel || !isCollapsed)"
        ref="collapsedIcon"
      >
        <span class="icon collapsedIcon"></span>
      </div>
    </div>
    <Transition name="collapse">
      <div class="Body PanelBody fit" :class="bodyclass">
        <slot v-if="renderCollapsed" />
      </div>
    </Transition>
  </div>
</template>

<script>
export default {
  name: "Panel",
  emits: ["onCollapse"],
  props: {
    title: String,
    size: String,
    bubble: String, //tab panel leírás
    h: Boolean,
    c: Boolean,
    collapsed: Boolean,
    collapsible: Boolean,
    window: Boolean,
    border: Boolean,
    gap: Boolean,
    padding: Boolean,
    frame: Boolean,
    opened: Boolean,
    if: String, // Field alapján beazonosítás
    tabId: String
  },
  data: function () {
    return {
      isCollapsed: this.collapsed || this.$parent.$options.name == "Accordion",
      isAccordion: this.$parent.$options.name == "Accordion",
      collapseDirection: false,
      hidden: false, //tab panel miatt
      render: this.$parent.$options.name != "Tab",
      noTabParent: this.$parent.$options.name != "Tab",
      renderCollapsed: !(
        this.collapsed || this.$parent.$options.name == "Accordion"
      ),
      doCollapsible: this.collapsible,
      vPanel: false,
      hPanel: false,
    };
  },
  computed: {
    panelflex: function () {
      if (!this.size) return "flex: 0 1 100%";
      if (this.size.includes("%")) return "flex: 0 1 " + this.size;
      return "flex: 0 0 " + this.size + (!this.size.includes("px") ? "px" : "");
    },
    hasTieleSlot() {
      return (
        this.$slots.title ||
        this.$slots.titlecollapsed ||
        this.$slots.titlebuttons
      );
    },
    bodyclass: function () {
      return {
        vflex: !this.h && !this.c,
        hflex: this.h,
        cflex: this.c,
        gap: this.gap,
        padding: this.padding,
        frame: this.frame,
      };
    },
  },
  mounted: function () {
    this.hPanel = this.isParentHflex();
    this.vPanel = !this.hPanel;
    if (this.doCollapsible) {
      //ikon pozicionálása
      let fc = this.isFirstChild(),
        lc = this.isLastChild(),
        h = this.isParentHflex(),
        v = this.isParentVflex();
      if (h && fc) this.collapseDirection = "up";
      else if (h && lc) this.collapseDirection = "down";
      else if (v && fc) this.collapseDirection = "left";
      else if (v && lc) this.collapseDirection = "right";
      else this.collapseDirection = "left";
      // dd(this.title, fc, lc, h, v, this.collapseDirection);
    }
  },
  watch: {
    collapsed: function (val) {
      this.isCollapsed = val ? true : false;
    },
    isCollapsed: function (val) {
      this.$emit("onCollapse", val, this);
      if (!val) this.renderCollapsed = true;
      var Accordion = this.up("Accordion");
      if (Accordion) Accordion.onPanelCollapseChange(this, val);
    },
  },
  methods: {
    collapseClick: function (e) {
      if (!this.doCollapsible) return;
      e.stopPropagation();

      if (
        !this.isAccordion &&
        (this.$slots.title || this.$slots.titlecollapsed)
      ) {
        //ha vannak gombok a tile-ben akkor csak az ikonnal lehessen becsukni / kinyitni
        if (!this.$refs.collapsedIcon.contains(e.target)) return;
      }

      if (this.hover && this.isCollapsed) {
        this.isCollapsed = true;
      } else {
        this.isCollapsed = !this.isCollapsed;
      }
    },
    onClick: function (e) {
      if (!this.doCollapsible) return;
      if (this.isCollapsed) {
        e.stopPropagation();
        this.isCollapsed = false;
      }
    },
    isFirstChild: function () {
      let c = this.$el.parentElement.children;
      return c.length > 0 && c[0] === this.$el;
    },
    isLastChild: function () {
      let c = this.$el.parentElement.children;
      return c.length > 0 && c[c.length - 1] === this.$el;
    },
    isParentHflex: function () {
      if (!this.$el) return false;
      return this.$el.parentNode.classList.contains("hflex");
    },
    isParentVflex: function () {
      if (!this.$el) return true;
      return this.$el.parentNode.classList.contains("vflex");
    },
    hide: function () {
      this.hidden = true;
    },
    show: function () {
      this.hidden = false;
    },
  },
};
</script>


<style>
.Panel.PanelBorder > .titleBorder,
.Panel.collapsible > .titleBorder {
  font-weight: bold;
  background-color: var(--title-background-color);
  color: var(--title-color);
  height: 50px;
  padding: 0 10px;
}

.Panel.notCollapsible.notPanelBorder > .titleBorder {
  padding: 10px;
  font-weight: bold;
}

.Panel.collapsed {
  flex: 0 0 51px !important;
}
.Panel.collapsible.collapsed > .Title {
  border: none;
}

.Panel > .titleBorder {
  white-space: nowrap;
  text-align: left;
  position: relative;
}

.vPanel.collapsible.collapsed > .titleBorder {
  flex: 1 1 auto;
  padding: 0;
}

.Panel.collapsible.collapsed .left,
.Panel.collapsible.collapsed .right {
  -ms-writing-mode: tb-lr;
  -webkit-writing-mode: vertical-lr;
  writing-mode: vertical-lr;
}

.vPanel.collapsed > .Body {
  display: none;
}

.hPanel.collapsed > .Body {
  display: none;
}

.hflex .Panel.collapsed {
  min-height: 0px;
}

.vflex .Panel.collapsed {
  min-width: 0px;
}

.hPanel.notCollapsed {
  transition: flex-basis 0.5s ease;
}

.vPanel {
  transition: flex-basis 0.2s ease;
}

.windowPanel {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  box-sizing: border-box;
  border: none;
  border-radius: 0;
  z-index: 20;
}

.hPanel .paneltitlebuttons,
.vPanel.notCollapsed .paneltitlebuttons {
  position: absolute;
  top: 50%;
  right: 0;
  transform: translate(100%, -50%);
  padding: 0 10px;
  width: max-content;
}
.vPanel.collapsed .paneltitlebuttons {
  position: absolute;
  transform: translate(-50%, 100%);
  padding: 10px 0;
  height: max-content;
  bottom: 0;
  left: 50%;
}

.PanelBody.frame {
  border-radius: 0;
  background-color: unset;
}
</style>