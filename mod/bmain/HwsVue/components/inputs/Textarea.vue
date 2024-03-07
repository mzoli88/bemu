<template>
  <div class="fieldbody fit vflex">
    <textarea
      type="text"
      class="Textfield Textarea fit fieldBorder"
      :class="{ required }"
      v-model="value"
      autocomplete="off"
      :disabled="disabled"
      :style="{ height: $attrs.height }"
    />
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
};
</script>