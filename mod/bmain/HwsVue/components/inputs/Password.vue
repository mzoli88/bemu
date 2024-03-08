<template>
  <div class="passwordField fieldbody fit vflex Numberfield">
    <input
      :type="showpw ? 'text' : 'password'"
      class="Textfield fit fieldBorder"
      :class="{ required }"
      v-model="value"
      :disabled="disabled"
      :name="name"
      :autocomplete="autocomplete"
    />
    <slot />
    <div
      @click="toogle"
      :tabindex="disabled ? null : 0"
      @keydown.space="toogle"
      class="fieldButton cflex pointer noselect"
    >
      <Icon v-if="showpw">f070</Icon>
      <Icon v-else>f06e</Icon>
    </div>
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
  props: {
    autocomplete: String,
    name: String,
  },
  data: function () {
    return {
      showpw: false,
    };
  },
  methods: {
    toogle: function () {
      this.showpw = !this.showpw;
    },
  },
};
</script>