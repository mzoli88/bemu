<template>
  <button class="Button noselect pointer cflex" type="button" :title="getTitle()" :class="{
    active: active,
    disable: disable || (quequeDisable && Queque.run),
    pictoBtn: isPicto,
    highlight: isHighlight,
  }" @click="onClick" @focus="onFocus" @blur="onBlur">
    <Icon v-if="!noSpin && MajaxManager.loading" icon="f013" spin />
    <Icon v-if="noSpin || !MajaxManager.loading" :icon="icon" :title="seenTitle" :spin="spin" />
    <div class="ButtonText confText cflex" v-if="conf_state > 0">
      {{
    conf_state == 1
      ? "Megerősítés?"
      : "Megerősítés(" + (conf_state - 1) + ")"
  }}
    </div>
    <div v-if="!isPicto && !(conf_state > 0)" class="ButtonText cflex">
      <slot />
    </div>
    <slot name="notText" />
  </button>
</template>

<script>
export default {
  name: "Button",

  emits: ["click", "focus", "blur"],

  data: function name() {
    var otherTitle;

    if (this.hasSlot()) {
      otherTitle = this.$slots.default()[0].children.trim();
    }
    return {
      isHighlight: this.highlight,
      seenTitle: this.title || otherTitle,
      isPicto: !this.hasSlot(),
      conf_state: 0,
      Queque: Queque,
      MajaxManager: MajaxManager,
    };
  },
  mounted: function () {
    this.highlighting();
  },
  updated: function () {
    this.highlighting();
  },
  methods: {
    getTitle: function () {
      var out = this.seenTitle;
      if (empty(out)) return null;

      if (this.conf_state == 1) out + " (Kattintson a megerősítéshez!)";
      if (this.quequeDisable && this.Queque.run)
        return out + " (" + this.Queque.name + " folyamatban van)";

      return out;
    },
    countConf: function () {
      if (this.conf_state <= 1) return;
      this.conf_state--;
      setTimeout(() => this.countConf(), 600);
    },
    highlighting: function () {
      if (this.noHighlight) return;
      //higlight beállítás
      var el = this.$el,
        parent = el.parentNode;
      //ha első gomb
      if (parent.firstElementChild == el) {
        if (!parent.querySelector(".highlight")) {
          el.classList.add("highlight");
        }
      }
    },
    onClick: function (e) {
      if (this.MajaxManager.loading) return;
      if (this.conf) {
        if (this.conf_state == 1) {
          this.conf_state = 0;
          this.$emit("click", e, this);
          return;
        }
        if (this.conf_state == 0) {
          this.conf_state = 5;
          this.countConf();
          this.$el.focus();
          return;
        }
        return;
      }
      e.stopPropagation() ||
        this.disable ||
        (this.quequeDisable && this.Queque.run) ||
        this.$emit("click", e, this);
    },
    onFocus: function (e) {
      e.stopPropagation() ||
        this.disable ||
        (this.quequeDisable && this.Queque.run) ||
        this.$emit("focus", e, this);
    },
    onBlur: function (e) {
      this.$emit("blur", e, this);
      if (this.conf_state > 0) this.conf_state = 0;
    },
  },
  props: {
    title: String,
    icon: String,
    active: Boolean,
    highlight: Boolean,
    noHighlight: Boolean,
    disable: Boolean,
    spin: Boolean,
    quequeDisable: Boolean,
    conf: Boolean,
    noSpin: Boolean,
  },
};
</script>

<style>
.Button {
  outline: none;
  position: relative;
  display: flex;
  flex-direction: row;
  gap: 5px;
  align-items: stretch;
  min-width: 28px;
}

.Button.pictoBtn {
  justify-content: center;
}

.Button>.icon {
  font-size: calc(15px + var(--button-size));
}

.Button {
  font-size: calc(12px + var(--button-size));
  /* font-weight: bold; */
  color: var(--higlight-text-color);
  padding: 3px;
  border: 1px solid #d5d7db;
  background-color: #f9f9fc;
  border-radius: 4px;
}

.Button.highlight {
  background-color: var(--button-higlight-color);
  color: #fff;
  border-color: transparent;
}

.Button:active {
  color: #fff;
  background-color: #515e6f;
  border-color: transparent;
}
</style>