<template>
  <div class="fieldbody displayBody fit vflex">
    <div class="fieldBorder fit" v-html="convInt(value)"></div>
    <slot />
  </div>
</template>

<script>
export default {
  // :align="inputAlign"
  // props: {
  //   inputAlign: String,
  // },
  mixins: [fieldMixin],
  methods: {
    convInt: function (number) {
      if (!isNumber(number)) return number;
      return number.toString().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1 ");
    },
  },
};
</script>

<style>
.displayBody {
  overflow: auto;

  white-space: -moz-pre-wrap;
  white-space: -pre-wrap;
  white-space: -o-pre-wrap;
  white-space: pre-wrap;

  overflow-wrap: break-word;
  word-wrap: break-word;

  -ms-word-break: break-all;
  word-break: break-word;

  -ms-hyphens: auto;
  -moz-hyphens: auto;
  -webkit-hyphens: auto;
  hyphens: auto;
}
.displayBody .fieldBorder {
  border: none;
  background-color: transparent;
  font-weight: bold;
}
</style>