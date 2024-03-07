<template>
  <div
    class="dropdown"
    @focus="$emit('focus', $event, _self)"
    @blur="obBlur"
    @keydown="onKeydown"
    :style="{
      top: top + 'px',
      left: left + 'px',
      'max-width': maxWidth + 'px',
      'max-height': maxHeight + 'px',
    }"
    tabindex="0"
  >
    <slot />
  </div>
</template>

<script>
export default {
  props: {
    noDfocus: Boolean,
  },
  emits: ["blur", "keydown", "focus"],
  data: function () {
    return {
      top: 0,
      left: 0,
      maxWidth: 0,
      maxHeight: 250,
      faceUp: false,
    };
  },
  mounted: function () {
    document.addEventListener("wheel", this.onWeel, {
      passive: false,
      capture: true,
    });
    var parentPos = this.getParent().getBoundingClientRect();
    this.left = parentPos.x;
    this.maxWidth = parentPos.width;
    this.top = parentPos.y + parentPos.height;

    //ha pl dátum intervallum választó van akkor mutassa, hogy éppen melyik mezőt szerkeszti a datepicker segítségével
    if (!this.noDfocus) this.$el.parentElement.classList.add("dummyFocus");

    this.$nextTick(function () {
      this.setToView();
    });
  },
  updated: function () {
    this.setToView();
  },
  methods: {
    getParent() {
      //ha intervallum van akkor a szülő kisebb
      var p = this.$el.closest(".intervalFieldCt");
      if (p) return p;
      return this.$el.parentElement;
    },
    onWeel: function (e) {
      if (!this.$el || !this.getParent()) {
        document.removeEventListener("wheel", this.onWeel, {
          passive: false,
          capture: true,
        });
      }
      if (!this.$el.contains(e.target)) e.preventDefault();
    },

    setToView: function () {
      var parentPos = this.getParent().getBoundingClientRect(),
        pos = this.$el.getBoundingClientRect(),
        notInView =
          pos.bottom >
          (window.innerHeight || document.documentElement.clientHeight);
      if (notInView) {
        this.faceUp = true;
        var nexPos = parentPos.y - pos.height;
        if (nexPos < 1) {
          this.top = 1;
          this.maxHeight = this.maxHeight + nexPos;
        } else {
          this.top = nexPos;
        }
      }
    },
    obBlur: function (e) {
      if (!this.noDfocus) this.$el.parentElement.classList.remove("dummyFocus"); //szülő komponens látszólagos fokusz megszüntetése
      if (this.$el.contains(e.relatedTarget)) {
        e.preventDefault();
        this.focus();
      } else {
        this.$emit("blur", e, this);
      }
    },
    onKeydown: function (e) {
      //ESC gomb esetén zárja be a panelt
      if (e.keyCode == 27) {
        this.$emit("blur", e, this);
      } else {
        this.$emit("keydown", e, this);
      }
    },
    focus: function () {
      this.$el.focus();
    },
  },
  destroyed: function () {
    document.removeEventListener("wheel", this.onWeel, {
      passive: false,
      capture: true,
    });
  },
};
</script>

<style>
.dropdown {
  white-space: nowrap;
  position: fixed;
  min-height: 20px;
  white-space: initial;
  z-index: 100;
  min-width: 100px;
}
</style>