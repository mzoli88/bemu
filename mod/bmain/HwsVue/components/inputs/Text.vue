<template>
  <div class="fieldbody fit vflex">
    <input
      type="text"
      class="Textfield fit fieldBorder"
      :class="{ required }"
      v-model="value"
      :disabled="disabled"
      ref="input"
      :name="autocomplete ? name : null"
      :autocomplete="autocomplete || 'off'"
    />
    <slot />
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
  props: {
    autocomplete: String,
    name: String,
  },
};
</script>