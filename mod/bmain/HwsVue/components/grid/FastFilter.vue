<template>
  <div class="fastFilter toolbar" v-show="showToolbar()">
    <Button
      v-for="(btn, key) in buttons"
      :key="key + active"
      :highlight="key == active"
      @click="onClick(key)"
      :title="btn.title"
      >{{ btn.text }}</Button
    >
  </div>
</template>

<script>
export default {
  props: {
    data: Object,
  },
  computed: {
    buttons: function () {
      var out = {};
      for (let i in this.data) {
        if (this.data[i] === false || empty(this.data[i])) continue;
        let tmp = this.data[i].split("|");
        out[i] = {
          text: tmp.shift(),
          title: tmp.shift(),
        };
      }
      return out;
    },
  },
  data: function () {
    return {
      active: this.$attrs.fastFilterValue,
    };
  },
  watch: {
    active: function (val) {
      this.up("Grid").fastFilterValue = val;
    },
  },
  methods: {
    showToolbar: function () {
      let count = 0;
      for (let index in this.data) {
        if (this.data[index] != false) count++;
        if (count > 1) return true;
      }
      //ha csak 1 paraméter van akkor el kell rejteni a panelt
      for (let index in this.data) {
        if (this.data[index] != false) {
          this.active = index;
          break;
        }
      }
      return false;
    },
    onClick: function (key) {
      this.active = key;
    },
  },
};
</script>