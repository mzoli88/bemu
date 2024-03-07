<template>
  <div class="fieldbody FileField fit vflex pointer" @click.self="onClick">
    <input
      type="text"
      class="Textfield fakeFileimput fit nopointerevent pointer fieldBorder"
      :class="{ required }"
      autocomplete="off"
      readonly
      :placeholder="value ? value.name : 'Fájl kiválasztása...'"
      v-show="!loading"
      @keypress="onKeypress"
      :disabled="disabled"
    />
    <input
      type="file"
      class="hiddenfileimput"
      @change="fileChange"
      ref="filefield"
      tabindex="-1"
      :accept="accept"
      :multiple="multiple"
    />
    <div class="fieldButton cflex nopointerevent pointer">
      <span class="icon upload"></span>
    </div>
  </div>
</template>

<script>
export default {
  mixins: [fieldMixin],
  data: function () {
    return {
      loading: false,
    };
  },
  props: {
    multiple: Boolean,
    maxByte: Number,
    accept: String,
  },
  watch: {
    value: function (val) {
      if (!val) this.reset();
    },
  },
  methods: {
    addData: function (val) {
      this.value = val;
    },
    fileChange: function (event) {
      if (event.target.files.length == 0) return;
      this.loading = true;
      var me = this;
      // file = event.target.files[0],
      // reader = new FileReader();

      // dd (event.target.files);

      for (var index = 0; index < event.target.files.length; index++) {
        let reader = new FileReader();
        let file = event.target.files[index];
        reader.onload = (function (theFile) {
          me.loading = false;
          if (me.maxByte && me.maxByte < file.size) {
            msg("Fájl mérete túl nagy!", "error");
            return false;
          }
          if (me.accept) {
            var tmpaccept = me.accept.split(",").map((x) => x.trim());
            if (!tmpaccept.includes(file.type)) {
              msg("Fájl típusa nem engedélyezett!", "error");
              return false;
            }
          }
          // dd ('name',file.name);
          return function (e) {
            me.value = {
              name: file.name,
              size: file.size,
              type: file.type,
              file_content: e.target.result.replace(/^data:.*;base64,/, ""),
            };
          };
        })(file);
        reader.readAsDataURL(file);
      }
    },
    onKeypress: function (e) {
      switch (e.keyCode) {
        case 32: //space
          this.$refs.filefield.click();
          this.$el.focus();
          break;
      }
    },
    reset: function () {
      this.value = null;
      this.$refs.filefield.value = "";
    },
    onClick: function (e) {
      if (this.disabled) return false;
      this.$refs.filefield.click();
      this.$el.focus();
    },
  },
};
</script>

<style>
.hiddenfileimput {
  opacity: 0;
  padding: 0;
  margin: 0;
  border: none;
  position: absolute;
  width: 100%;
  height: 100%;
}
.nopointerevent {
  pointer-events: none;
}
</style>