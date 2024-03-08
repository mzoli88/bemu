<template>
  <div class="ColorField fieldbody fit vflex Numberfield">
    <input
      type="text"
      class="Textfield fit fieldBorder"
      :class="{ required }"
      v-model="value"
      autocomplete="off"
      :disabled="disabled"
    />
    <div
      :tabindex="disabled ? null : 0"
      class="fieldButton cflex pointer noselect"
      :style="{ color: value }"
    >
      <input
        type="color"
        class="fieldButton colorPicker pointer"
        :class="{ required }"
        v-model="value2"
        autocomplete="off"
        :disabled="disabled"
      />
      <Icon>f53f</Icon>
    </div>
    <slot />
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
  data: function () {
    return {
      value2: "#000000",
    };
  },
  watch: {
    value2: function (val) {
      this.value = val;
    },
    value: function (val) {
      if (val && /^#([0-9A-F]{3}){1,2}$/i.test(val)) {
        this.value2 = val;
      }
    },
  },
};
</script>

<style>
/* .ColorField > .fieldBorder { */
/* padding: 0; */
/* } */
.colorPicker {
  outline: none;
  padding: 0;
  opacity: 0;
  position: absolute !important;
}
</style>